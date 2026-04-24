<?php

declare(strict_types=1);

namespace App\Message;

use DateTimeImmutable;

final class OrderProcessingStatusMessage
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $type,
        public readonly DateTimeImmutable $createdAt,
        public readonly string $orderId,
        public readonly string $orderEventId,
        public readonly string $productId,
        public readonly string $status,
        public readonly ?string $error,
    ) {
    }
}
