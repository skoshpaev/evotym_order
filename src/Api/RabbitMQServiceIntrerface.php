<?php

declare(strict_types=1);

namespace App\Api;

use App\Message\OrderCreatedMessage;
use App\Message\OrderProcessingStatusMessage;
use App\Message\ProductUpdatedMessage;

interface RabbitMQServiceIntrerface
{
    public const MESSAGE_TYPE_PRODUCT_UPDATED = 'product.updated';
    public const MESSAGE_TYPE_ORDER_CREATED = 'order.created';
    public const MESSAGE_TYPE_ORDER_PROCESSING_STATUS = 'order.processing.status';

    public const ORDER_PROCESSING_STATUS_PROCESSED = 'processed';
    public const ORDER_PROCESSING_STATUS_FAILED = 'failed';

    public function productUpdated(ProductUpdatedMessage $message): void;

    public function orderProcessingStatus(OrderProcessingStatusMessage $message): void;

    public function orderCreated(OrderCreatedMessage $message): string;

    public function publishOutboxMessage(string $eventId): bool;

    public function publishPendingOutbox(): int;
}
