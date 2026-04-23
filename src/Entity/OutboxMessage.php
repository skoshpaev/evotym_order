<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OutboxMessageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OutboxMessageRepository::class)]
#[ORM\Table(name: 'outbox')]
final class OutboxMessage
{
    public const STATUS_CREATED = 'created';
    public const STATUS_PUBLISHED = 'published';

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
        DateTimeImmutable $createdAt,
    ) {
        $this->eventId = $eventId;
        $this->event = $event;
        $this->productId = $productId;
        $this->eventType = $eventType;
        $this->status = self::STATUS_CREATED;
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
        DateTimeImmutable $createdAt,
    ): self {
        return new self($eventId, $event, $productId, $eventType, $createdAt);
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getEvent(): array
    {
        return $this->event;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function isCreated(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    public function markPublished(): void
    {
        $this->status = self::STATUS_PUBLISHED;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
