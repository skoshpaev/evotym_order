<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProductUpdatedMessage;
use App\Service\Api\IntegrationEventLoggerServiceInterface;
use App\Service\Api\RabbitMQServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler(fromTransport: 'product_updated')]
final class ProductUpdatedMessageHandler
{
    public function __construct(
        private readonly RabbitMQServiceInterface $rabbitMQService,
        private readonly IntegrationEventLoggerServiceInterface $integrationEventLogger,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(ProductUpdatedMessage $message): void
    {
        try {
            $this->rabbitMQService->productUpdated($message);
        } catch (Throwable $exception) {
            $this->integrationEventLogger->error(
                'Product was not updated in order service.',
                [
                    'eventId'   => $message->eventId,
                    'productId' => $message->id,
                    'type'      => $message->type,
                    'createdAt' => $message->createdAt->format(DATE_ATOM),
                    'error'     => $exception->getMessage(),
                ],
            );

            throw $exception;
        }
    }
}
