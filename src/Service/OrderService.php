<?php

declare(strict_types=1);

namespace App\Service;

use App\Api\OrderServiceIntrerface;
use App\Api\RabbitMQServiceIntrerface;
use App\Dto\CreateOrderRequestDto;
use App\Entity\Order;
use App\Message\OrderCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class OrderService implements OrderServiceIntrerface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RabbitMQServiceIntrerface $rabbitMQService,
    ) {
    }

    public function create(CreateOrderRequestDto $createOrderRequestDto): Order
    {
        $product = $createOrderRequestDto->product;

        $order = new Order();
        $order->setId(Uuid::v7()->toRfc4122());
        $order->setProduct($product);
        $order->setCustomerName($createOrderRequestDto->customerName);
        $order->setQuantityOrdered($createOrderRequestDto->quantityOrdered);
        $order->setOrderStatus(self::STATUS_PROCESSING);

        $this->entityManager->persist($order);
        $eventId = $this->rabbitMQService->orderCreated(
            new OrderCreatedMessage(
                Uuid::v7()->toRfc4122(),
                RabbitMQServiceIntrerface::MESSAGE_TYPE_ORDER_CREATED,
                new \DateTimeImmutable(),
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
