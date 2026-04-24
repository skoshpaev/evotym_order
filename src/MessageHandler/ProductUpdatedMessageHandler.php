<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Api\IntegrationEventLoggerServiceIntrerface;
use App\Api\RabbitMQServiceIntrerface;
use App\Message\ProductUpdatedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'product_updated')]
final class ProductUpdatedMessageHandler
{
    public function __construct(
        private readonly RabbitMQServiceIntrerface $rabbitMQService,
        private readonly IntegrationEventLoggerServiceIntrerface $integrationEventLogger,
    ) {
    }

    public function __invoke(ProductUpdatedMessage $message): void
    {
        try {
            $this->rabbitMQService->productUpdated($message);
        } catch (\Throwable $exception) {
            $this->integrationEventLogger->error(
                'Product was not updated in order service.',
                [
                    'eventId' => $message->eventId,
                    'productId' => $message->id,
                    'type' => $message->type,
                    'createdAt' => $message->createdAt->format(DATE_ATOM),
                    'error' => $exception->getMessage(),
                ],
            );

            throw $exception;
        }
    }
}
