<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\CreateOrderRequestDto;
use App\Entity\Order;
use App\Entity\Product;
use App\Message\OrderCreatedMessage;
use App\Service\OrderService;
use App\Service\RabbitMQServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class OrderServiceTest extends TestCase
{
    public function testCreatePersistsOrderAndDispatchesReservationRequestWithProductVersion(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $rabbitMqService = $this->createMock(RabbitMQServiceInterface::class);
        $service = new OrderService($entityManager, $rabbitMqService);
        $product = Product::create(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            4,
        );
        $dto = new CreateOrderRequestDto($product, 'John Doe', 2);
        $eventId = '019dbb30-e0f0-7c26-9cb5-b12b0de9d9eb';

        $persistedOrder = null;

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (object $entity) use (&$persistedOrder): bool {
                $persistedOrder = $entity;

                return $entity instanceof Order;
            }));

        $entityManager->expects(self::once())->method('flush');

        $rabbitMqService
            ->expects(self::once())
            ->method('orderCreated')
            ->with(self::callback(static function (OrderCreatedMessage $message) use ($product, &$persistedOrder): bool {
                return $persistedOrder instanceof Order
                    && $message->orderId === $persistedOrder->getId()
                    && $message->productId === $product->getId()
                    && $message->quantityOrdered === 2
                    && $message->expectedProductVersion === 4;
            }))
            ->willReturn($eventId);

        $rabbitMqService
            ->expects(self::once())
            ->method('publishOutboxMessage')
            ->with($eventId);

        $order = $service->create($dto);

        self::assertInstanceOf(Order::class, $order);
        self::assertSame(Order::STATUS_PROCESSING, $order->getOrderStatus());
        self::assertSame('John Doe', $order->getCustomerName());
        self::assertSame(2, $order->getQuantityOrdered());
    }
}
