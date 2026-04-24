<?php
/** @noinspection PhpUnused */

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

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function setEventId(string $eventId): void
    {
        $this->eventId = $eventId;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getEvent(): array
    {
        return $this->event;
    }

    /**
     * @param array<string, scalar|null> $event
     */
    public function setEvent(array $event): void
    {
        $this->event = $event;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
