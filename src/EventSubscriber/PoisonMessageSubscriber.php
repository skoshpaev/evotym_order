<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Api\PoisonMessageRecorderServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Throwable;

final class PoisonMessageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PoisonMessageRecorderServiceInterface $poisonMessageRecorder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', -150],
        ];
    }

    /** @noinspection PhpUnused */
    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        try {
            $this->poisonMessageRecorder->record(
                $event->getEnvelope()->getMessage(),
                $event->getThrowable(),
                $this->resolveFailureTransportName($event->getReceiverName()),
            );
        } catch (Throwable $throwable) {
            error_log(sprintf('Could not record poison message: %s', $throwable->getMessage()));
        }
    }

    private function resolveFailureTransportName(string $receiverName): string
    {
        if (str_ends_with($receiverName, '_failed')) {
            return $receiverName;
        }

        return sprintf('%s_failed', $receiverName);
    }
}
