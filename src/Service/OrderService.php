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

        $order = Order::create(
            $product,
            $createOrderRequestDto->customerName,
            $createOrderRequestDto->quantityOrdered,
        );

        $this->entityManager->persist($order);
        $eventId = $this->rabbitMQService->orderCreated(
            OrderCreatedMessage::create(
                $order->getId(),
                $product->getId(),
                $createOrderRequestDto->quantityOrdered,
                $product->getVersion(),
            ),
        );
        $this->entityManager->flush();
        $this->rabbitMQService->publishOutboxMessage($eventId);

        return $order;
    }
}
