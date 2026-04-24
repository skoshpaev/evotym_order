<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use App\Service\Api\OrderServiceInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Product $product;

    #[ORM\Column(name: 'customer_name', length: OrderServiceInterface::CUSTOMER_NAME_MAX_LENGTH)]
    private string $customerName;

    #[ORM\Column(name: 'quantity_ordered')]
    private int $quantityOrdered;

    #[ORM\Column(name: 'order_status', length: 32)]
    private string $orderStatus;

    #[ORM\Column(name: 'last_processing_status_event_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastProcessingStatusEventAt = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function getQuantityOrdered(): int
    {
        return $this->quantityOrdered;
    }

    public function setQuantityOrdered(int $quantityOrdered): void
    {
        $this->quantityOrdered = $quantityOrdered;
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(string $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    public function getLastProcessingStatusEventAt(): ?DateTimeImmutable
    {
        return $this->lastProcessingStatusEventAt;
    }

    public function setLastProcessingStatusEventAt(?DateTimeImmutable $lastProcessingStatusEventAt): void
    {
        $this->lastProcessingStatusEventAt = $lastProcessingStatusEventAt;
    }
}
