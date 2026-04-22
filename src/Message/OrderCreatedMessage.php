<?php

declare(strict_types=1);

namespace App\Message;

final class OrderCreatedMessage
{
    public function __construct(
        public readonly string $productId,
        public readonly int $quantity,
    ) {
    }
}
