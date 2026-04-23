<?php

declare(strict_types=1);

namespace App\Message;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class ProductUpdatedMessage
{
    public const TYPE = 'product.updated';

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

    public static function fromPayload(
        string $id,
        string $name,
        float $price,
        int $quantity,
        int $version,
        ?string $eventId = null,
        ?string $type = null,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            $eventId ?? Uuid::v7()->toRfc4122(),
            $type ?? self::TYPE,
            $createdAt ?? new DateTimeImmutable(),
            $id,
            $name,
            $price,
            $quantity,
            $version,
        );
    }
}
