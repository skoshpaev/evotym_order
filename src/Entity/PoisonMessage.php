<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'poison_messages')]
final class PoisonMessage
{
    public const STATUS_POISONED = 'poisoned';

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

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        ?string $eventId,
        ?string $eventType,
        string $messageClass,
        string $failureTransportName,
        array $payload,
        string $errorMessage,
    ) {
        $this->eventId = $eventId;
        $this->eventType = $eventType;
        $this->messageClass = $messageClass;
        $this->failureTransportName = $failureTransportName;
        $this->payload = $payload;
        $this->errorMessage = $errorMessage;
        $this->status = self::STATUS_POISONED;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function create(
        ?string $eventId,
        ?string $eventType,
        string $messageClass,
        string $failureTransportName,
        array $payload,
        string $errorMessage,
    ): self {
        return new self(
            $eventId,
            $eventType,
            $messageClass,
            $failureTransportName,
            $payload,
            $errorMessage,
        );
    }
}
