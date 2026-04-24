<?php

declare(strict_types=1);

namespace App\Message;

use DateTimeImmutable;

final class ProductUpdatedMessage
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $type,
        public readonly DateTimeImmutable $createdAt,
        public readonly string $id,
        public readonly string $name,
        public readonly float $price,
        public readonly int $quantity,
        public readonly int $version,
    ) {
    }
}
