<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InboxMessageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InboxMessageRepository::class)]
#[ORM\Table(name: 'inbox')]
final class InboxMessage
{
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IGNORED = 'ignored';

    #[ORM\Id]
    #[ORM\Column(name: 'event_id', type: Types::STRING, length: 36, unique: true)]
    private string $eventId;

    /** @var array<string, scalar|null> */
    #[ORM\Column(name: 'event', type: Types::JSON)]
    private array $event;

    #[ORM\Column(name: 'product_id', type: Types::STRING, length: 36, nullable: true)]
    private ?string $productId;

    #[ORM\Column(name: 'event_type', length: 64)]
    private string $eventType;

    #[ORM\Column(length: 32)]
    private string $status;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    /**
     * @param array<string, scalar|null> $event
     */
    private function __construct(
        string $eventId,
        array $event,
        ?string $productId,
        string $eventType,
        string $status,
        DateTimeImmutable $createdAt,
    ) {
        $this->eventId = $eventId;
        $this->event = $event;
        $this->productId = $productId;
        $this->eventType = $eventType;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    /**
     * @param array<string, scalar|null> $event
     */
    public static function create(
        string $eventId,
        array $event,
        ?string $productId,
        string $eventType,
        string $status,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($eventId, $event, $productId, $eventType, $status, $createdAt);
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getEvent(): array
    {
        return $this->event;
    }
}
