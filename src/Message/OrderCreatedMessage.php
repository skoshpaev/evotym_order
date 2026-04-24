<?php

declare(strict_types=1);

namespace App\Message;

use DateTimeImmutable;

final class OrderCreatedMessage
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $type,
        public readonly DateTimeImmutable $createdAt,
        public readonly string $orderId,
        public readonly string $productId,
        public readonly int $quantityOrdered,
        public readonly int $expectedProductVersion,
    ) {
    }
}
