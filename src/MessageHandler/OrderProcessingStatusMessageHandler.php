<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Api\IntegrationEventLoggerServiceIntrerface;
use App\Api\RabbitMQServiceIntrerface;
use App\Message\OrderProcessingStatusMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'order_processing_status')]
final class OrderProcessingStatusMessageHandler
{
    public function __construct(
        private readonly RabbitMQServiceIntrerface $rabbitMQService,
        private readonly IntegrationEventLoggerServiceIntrerface $integrationEventLogger,
    ) {
    }

    public function __invoke(OrderProcessingStatusMessage $message): void
    {
        try {
            $this->rabbitMQService->orderProcessingStatus($message);
        } catch (\Throwable $exception) {
            $this->integrationEventLogger->error(
                'Order processing status was not applied in order service.',
                [
                    'eventId' => $message->eventId,
                    'orderId' => $message->orderId,
                    'orderEventId' => $message->orderEventId,
                    'productId' => $message->productId,
                    'status' => $message->status,
                    'createdAt' => $message->createdAt->format(DATE_ATOM),
                    'error' => $exception->getMessage(),
                ],
            );

            throw $exception;
        }
    }
}
