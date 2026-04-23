<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\OutboxMessage;
use App\Entity\Product;
use App\Message\OrderCreatedMessage;
use App\Message\ProductUpdatedMessage;
use App\Repository\OutboxMessageRepository;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class RabbitMQService implements RabbitMQServiceInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ProductRepository $productRepository,
        private readonly OutboxMessageRepository $outboxMessageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IntegrationEventLogger $integrationEventLogger,
    ) {
    }

    public function productUpdated(ProductUpdatedMessage $message): void
    {
        $this->assertProductUpdatedMessageIsValid($message);

        $product = $this->productRepository->find($message->id);

        if ($product !== null && $this->isOldProductMessage($product, $message)) {
            $this->integrationEventLogger->warning(
                'Product update was skipped in order service because a stale event was received.',
                [
                    'eventId' => $message->eventId,
                    'productId' => $message->id,
                    'createdAt' => $message->createdAt->format(DATE_ATOM),
                ],
            );

            return;
        }

        if ($product === null) {
            $product = Product::create($message->id, $message->name, $message->price, $message->quantity, $message->version);
            $this->entityManager->persist($product);
        } else {
            $product->sync($message->name, $message->price, $message->quantity, $message->version);
        }

        $product->markProductEventProcessed($message->createdAt);
        $this->entityManager->flush();
    }

    public function orderCreated(OrderCreatedMessage $message): string
    {
        $this->entityManager->persist(
            OutboxMessage::create(
                $message->eventId,
                $this->normalizeOrderCreatedMessage($message),
                $message->productId,
                $message->type,
                $message->createdAt,
            ),
        );

        return $message->eventId;
    }

    public function publishOutboxMessage(string $eventId): bool
    {
        $outboxMessage = $this->outboxMessageRepository->find($eventId);

        if (!$outboxMessage instanceof OutboxMessage || !$outboxMessage->isCreated()) {
            return false;
        }

        return $this->publishMessage($outboxMessage);
    }

    public function publishPendingOutbox(): int
    {
        $published = 0;

        foreach ($this->outboxMessageRepository->findCreatedOrdered() as $outboxMessage) {
            if ($this->publishMessage($outboxMessage)) {
                ++$published;
            }
        }

        return $published;
    }

    private function isOldProductMessage(Product $product, ProductUpdatedMessage $message): bool
    {
        $lastProductEventAt = $product->getLastProductEventAt();

        return $lastProductEventAt !== null && $message->createdAt <= $lastProductEventAt;
    }

    private function assertProductUpdatedMessageIsValid(ProductUpdatedMessage $message): void
    {
        if ($message->quantity < 0) {
            throw new UnrecoverableMessageHandlingException('Product quantity must be greater than or equal to zero.');
        }

        if ($message->version <= 0) {
            throw new UnrecoverableMessageHandlingException('Product version must be greater than zero.');
        }
    }

    /**
     * @return array<string, scalar|null>
     */
    private function normalizeOrderCreatedMessage(OrderCreatedMessage $message): array
    {
        return [
            'eventId' => $message->eventId,
            'type' => $message->type,
            'createdAt' => $message->createdAt->format(DATE_ATOM),
            'orderId' => $message->orderId,
            'productId' => $message->productId,
            'quantityOrdered' => $message->quantityOrdered,
            'expectedProductVersion' => $message->expectedProductVersion,
        ];
    }

    private function denormalizeOutboxMessage(OutboxMessage $outboxMessage): object
    {
        $event = $outboxMessage->getEvent();

        return match ($outboxMessage->getEventType()) {
            OrderCreatedMessage::TYPE => OrderCreatedMessage::fromPayload(
                $this->requireString($event, 'orderId'),
                $this->requireString($event, 'productId'),
                $this->requireInt($event, 'quantityOrdered'),
                $this->requireInt($event, 'expectedProductVersion'),
                $this->requireString($event, 'eventId'),
                $this->requireString($event, 'type'),
                $this->requireDateTime($event, 'createdAt'),
            ),
            default => throw new \LogicException(sprintf('Unsupported outbox event type "%s".', $outboxMessage->getEventType())),
        };
    }

    private function publishMessage(OutboxMessage $outboxMessage): bool
    {
        try {
            $this->messageBus->dispatch($this->denormalizeOutboxMessage($outboxMessage));
            $outboxMessage->markPublished();
            $this->entityManager->flush();

            return true;
        } catch (\Throwable $exception) {
            $this->integrationEventLogger->warning(
                'Outbox event publication failed and will be retried later.',
                [
                    'eventId' => $outboxMessage->getEventId(),
                    'eventType' => $outboxMessage->getEventType(),
                    'error' => $exception->getMessage(),
                ],
            );

            return false;
        }
    }

    /**
     * @param array<string, scalar|null> $event
     */
    private function requireString(array $event, string $field): string
    {
        $value = $event[$field] ?? null;

        if (!\is_string($value) || $value === '') {
            throw new \LogicException(sprintf('Field "%s" must be a non-empty string.', $field));
        }

        return $value;
    }

    /**
     * @param array<string, scalar|null> $event
     */
    private function requireInt(array $event, string $field): int
    {
        $value = $event[$field] ?? null;

        if (!\is_int($value)) {
            throw new \LogicException(sprintf('Field "%s" must be an integer.', $field));
        }

        return $value;
    }

    /**
     * @param array<string, scalar|null> $event
     */
    private function requireDateTime(array $event, string $field): DateTimeImmutable
    {
        return new DateTimeImmutable($this->requireString($event, $field));
    }
}
