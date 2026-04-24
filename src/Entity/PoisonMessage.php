<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'poison_messages')]
final class PoisonMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'event_id', type: Types::STRING, length: 36, nullable: true)]
    private ?string $eventId;

    #[ORM\Column(name: 'event_type', type: Types::STRING, length: 128, nullable: true)]
    private ?string $eventType;

    #[ORM\Column(name: 'message_class', type: Types::STRING, length: 255)]
    private string $messageClass;

    #[ORM\Column(name: 'failure_transport_name', type: Types::STRING, length: 128)]
    private string $failureTransportName;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $payload;

    #[ORM\Column(name: 'error_message', type: Types::TEXT)]
    private string $errorMessage;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $status;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    public function setEventId(?string $eventId): void
    {
        $this->eventId = $eventId;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): void
    {
        $this->eventType = $eventType;
    }

    public function getMessageClass(): string
    {
        return $this->messageClass;
    }

    public function setMessageClass(string $messageClass): void
    {
        $this->messageClass = $messageClass;
    }

    public function getFailureTransportName(): string
    {
        return $this->failureTransportName;
    }

    public function setFailureTransportName(string $failureTransportName): void
    {
        $this->failureTransportName = $failureTransportName;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
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
