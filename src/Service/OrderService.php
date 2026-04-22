<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateOrderRequestDto;
use App\Entity\Order;
use App\Message\OrderCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;

final class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RabbitMQServiceInterface $rabbitMQService,
    ) {
    }

    public function create(CreateOrderRequestDto $createOrderRequestDto): Order
    {
        $product = $createOrderRequestDto->product;
        $product->decreaseQuantity($createOrderRequestDto->quantityOrdered);

        $order = Order::create(
            $product,
            $createOrderRequestDto->customerName,
            $createOrderRequestDto->quantityOrdered,
        );

        $this->entityManager->persist($order);
        $this->entityManager->flush();
        $this->rabbitMQService->orderCreated(
            new OrderCreatedMessage($product->getId(), $product->getQuantity()),
        );

        return $order;
    }
}
