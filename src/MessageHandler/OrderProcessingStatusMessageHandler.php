<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\OrderProcessingStatusMessage;
use App\Service\Api\IntegrationEventLoggerServiceInterface;
use App\Service\Api\RabbitMQServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler(fromTransport: 'order_processing_status')]
final class OrderProcessingStatusMessageHandler
{
    public function __construct(
        private readonly RabbitMQServiceInterface $rabbitMQService,
        private readonly IntegrationEventLoggerServiceInterface $integrationEventLogger,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(OrderProcessingStatusMessage $message): void
    {
        try {
            $this->rabbitMQService->orderProcessingStatus($message);
        } catch (Throwable $exception) {
            $this->integrationEventLogger->error(
                'Order processing status was not applied in order service.',
                [
                    'eventId'      => $message->eventId,
                    'orderId'      => $message->orderId,
                    'orderEventId' => $message->orderEventId,
                    'productId'    => $message->productId,
                    'status'       => $message->status,
                    'createdAt'    => $message->createdAt->format(DATE_ATOM),
                    'error'        => $exception->getMessage(),
                ],
            );

            throw $exception;
        }
    }
}
