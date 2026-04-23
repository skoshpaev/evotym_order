<?php

declare(strict_types=1);

namespace App\Message;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class OrderProcessingStatusMessage
{
    public const TYPE = 'order.processing.status';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

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

    public static function fromPayload(
        string $orderId,
        string $orderEventId,
        string $productId,
        string $status,
        ?string $error = null,
        ?string $eventId = null,
        ?string $type = null,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            $eventId ?? Uuid::v7()->toRfc4122(),
            $type ?? self::TYPE,
            $createdAt ?? new DateTimeImmutable(),
            $orderId,
            $orderEventId,
            $productId,
            $status,
            $error,
        );
    }
}
