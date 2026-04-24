<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Order;
use Evotym\SharedBundle\Dto\ProductViewDto;

final class OrderResponseDto
{
    public function __construct(
        public readonly string $orderId,
        public readonly ProductViewDto $product,
        public readonly string $customerName,
        public readonly int $quantityOrdered,
        public readonly string $orderStatus,
    ) {
    }

    public static function fromEntity(Order $order): self
    {
        return new self(
            $order->getId(),
            ProductViewDto::fromProduct($order->getProduct()),
            $order->getCustomerName(),
            $order->getQuantityOrdered(),
            $order->getOrderStatus(),
        );
    }

    /**
     * @return array{
     *     orderId: string,
     *     product: array{id: string, name: string, price: float, quantity: int},
     *     customerName: string,
     *     quantityOrdered: int,
     *     orderStatus: string
     * }
     */
    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'product' => $this->product->toArray(),
            'customerName' => $this->customerName,
            'quantityOrdered' => $this->quantityOrdered,
            'orderStatus' => $this->orderStatus,
        ];
    }
}
