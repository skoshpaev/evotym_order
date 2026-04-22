<?php

declare(strict_types=1);

namespace App\Message;

final class ProductUpdatedMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly float $price,
        public readonly int $quantity,
    ) {
    }
}
