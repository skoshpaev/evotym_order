<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProductUpdatedMessage;
use App\Service\RabbitMQServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'product_updated')]
final class ProductUpdatedMessageHandler
{
    public function __construct(
        private readonly RabbitMQServiceInterface $rabbitMQService,
    ) {
    }

    public function __invoke(ProductUpdatedMessage $message): void
    {
        $this->rabbitMQService->productUpdated($message);
    }
}
