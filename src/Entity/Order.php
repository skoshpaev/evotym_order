<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    public const CUSTOMER_NAME_MAX_LENGTH = 255;
    public const STATUS_PROCESSING = 'Processing';
    public const STATUS_PROCESSED = 'Processed';
    public const STATUS_FAILED = 'Failed';

    #[ORM\Id]
    #[ORM\Column(length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Product $product;

    #[ORM\Column(name: 'customer_name', length: self::CUSTOMER_NAME_MAX_LENGTH)]
    private string $customerName;

    #[ORM\Column(name: 'quantity_ordered')]
    private int $quantityOrdered;

    #[ORM\Column(name: 'order_status', length: 32)]
    private string $orderStatus;

    #[ORM\Column(name: 'last_processing_status_event_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastProcessingStatusEventAt = null;

    private function __construct(string $id, Product $product, string $customerName, int $quantityOrdered)
    {
        $this->id = $id;
        $this->product = $product;
        $this->customerName = $customerName;
        $this->quantityOrdered = $quantityOrdered;
        $this->orderStatus = self::STATUS_PROCESSING;
    }

    public static function create(Product $product, string $customerName, int $quantityOrdered): self
    {
        return new self(Uuid::v7()->toRfc4122(), $product, $customerName, $quantityOrdered);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getQuantityOrdered(): int
    {
        return $this->quantityOrdered;
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    public function markProcessed(): void
    {
        $this->orderStatus = self::STATUS_PROCESSED;
    }

    public function markFailed(): void
    {
        $this->orderStatus = self::STATUS_FAILED;
    }

    public function getLastProcessingStatusEventAt(): ?DateTimeImmutable
    {
        return $this->lastProcessingStatusEventAt;
    }

    public function markProcessingStatusEventProcessed(DateTimeImmutable $createdAt): void
    {
        $this->lastProcessingStatusEventAt = $createdAt;
    }
}
