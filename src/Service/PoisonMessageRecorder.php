<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PoisonMessage;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class PoisonMessageRecorder
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function record(object $message, \Throwable $throwable, string $failureTransportName): void
    {
        $payload = $this->normalizeValue(get_object_vars($message));
        $eventId = $this->extractStringProperty($message, 'eventId');
        $eventType = $this->extractStringProperty($message, 'type');

        $entityManager = $this->getEntityManager();
        $entityManager->persist(
            PoisonMessage::create(
                $eventId,
                $eventType,
                $message::class,
                $failureTransportName,
                \is_array($payload) ? $payload : ['value' => $payload],
                $throwable->getMessage(),
            ),
        );
        $entityManager->flush();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->managerRegistry->getManager();

        if ($entityManager instanceof EntityManagerInterface && $entityManager->isOpen()) {
            return $entityManager;
        }

        $resetManager = $this->managerRegistry->resetManager();

        if (!$resetManager instanceof EntityManagerInterface) {
            throw new \LogicException('Doctrine entity manager is not available for poison message recording.');
        }

        return $resetManager;
    }

    private function extractStringProperty(object $message, string $property): ?string
    {
        $value = get_object_vars($message)[$property] ?? null;

        return \is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if (\is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (\is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[(string) $key] = $this->normalizeValue($item);
            }

            return $normalized;
        }

        if (\is_object($value)) {
            return [
                '__class' => $value::class,
                'data' => $this->normalizeValue(get_object_vars($value)),
            ];
        }

        return (string) $value;
    }
}
