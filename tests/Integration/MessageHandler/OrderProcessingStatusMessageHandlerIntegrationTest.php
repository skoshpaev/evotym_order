<?php

declare(strict_types=1);

namespace App\Tests\Integration\MessageHandler;

use App\Entity\Order;
use App\Entity\Product;
use App\Message\OrderProcessingStatusMessage;
use App\MessageHandler\OrderProcessingStatusMessageHandler;
use App\Repository\InboxMessageRepository;
use App\Repository\OrderRepository;
use App\Service\Api\OrderServiceInterface;
use App\Service\Api\RabbitMQServiceInterface;
use App\Tests\Integration\DatabaseKernelTestCase;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

use Throwable;

use function assert;

final class OrderProcessingStatusMessageHandlerIntegrationTest extends DatabaseKernelTestCase
{
    protected function transportNames(): array
    {
        return ['order_created', 'product_updated', 'product_updated_failed', 'order_processing_status', 'order_processing_status_failed'];
    }

    /**
     * @throws Throwable
     */
    public function testProcessedStatusMarksOrderAsProcessed(): void
    {
        [$product, $order] = $this->createOrderFixture();

        $handler = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(OrderProcessingStatusMessageHandler::class);
        $repository = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(OrderRepository::class);
        $inboxRepository = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(InboxMessageRepository::class);

        assert($handler instanceof OrderProcessingStatusMessageHandler);
        assert($repository instanceof OrderRepository);
        assert($inboxRepository instanceof InboxMessageRepository);

        $message = new OrderProcessingStatusMessage(
            '019db9fd-a933-785a-9485-247a36155e3f',
            RabbitMQServiceInterface::MESSAGE_TYPE_ORDER_PROCESSING_STATUS,
            new DateTimeImmutable('2026-04-23T10:58:22+00:00'),
            $order->getId(),
            '019db9fd-a82a-7e90-a13c-ebb0e48c00b2',
            $product->getId(),
            RabbitMQServiceInterface::ORDER_PROCESSING_STATUS_PROCESSED,
            null,
        );

        $handler($message);

        $storedOrder = $repository->find($order->getId());
        $inboxMessage = $inboxRepository->find($message->eventId);

        self::assertNotNull($storedOrder);
        self::assertSame(OrderServiceInterface::STATUS_PROCESSED, $storedOrder->getOrderStatus());
        self::assertNotNull($storedOrder->getLastProcessingStatusEventAt());
        self::assertNotNull($inboxMessage);
        self::assertSame('processed', $inboxMessage->getStatus());
    }

    /**
     * @throws Throwable
     */
    public function testFailedStatusMarksOrderAsFailed(): void
    {
        [$product, $order] = $this->createOrderFixture();

        $handler = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(OrderProcessingStatusMessageHandler::class);
        $repository = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(OrderRepository::class);
        $inboxRepository = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(InboxMessageRepository::class);

        assert($handler instanceof OrderProcessingStatusMessageHandler);
        assert($repository instanceof OrderRepository);
        assert($inboxRepository instanceof InboxMessageRepository);

        $message = new OrderProcessingStatusMessage(
            '019db9fd-d456-7fe1-88d7-e60e2c100794',
            RabbitMQServiceInterface::MESSAGE_TYPE_ORDER_PROCESSING_STATUS,
            new DateTimeImmutable('2026-04-23T10:58:33+00:00'),
            $order->getId(),
            '019db9fd-d2ea-7882-9d08-157918f554c4',
            $product->getId(),
            RabbitMQServiceInterface::ORDER_PROCESSING_STATUS_FAILED,
            'Product version mismatch.',
        );

        $handler($message);

        $storedOrder = $repository->find($order->getId());
        $inboxMessage = $inboxRepository->find($message->eventId);

        self::assertNotNull($storedOrder);
        self::assertSame(OrderServiceInterface::STATUS_FAILED, $storedOrder->getOrderStatus());
        self::assertNotNull($storedOrder->getLastProcessingStatusEventAt());
        self::assertNotNull($inboxMessage);
        self::assertSame('processed', $inboxMessage->getStatus());
    }

    /**
     * @throws Throwable
     */
    public function testDuplicateStatusEventIsIgnoredByInbox(): void
    {
        [$product, $order] = $this->createOrderFixture();

        $handler = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(OrderProcessingStatusMessageHandler::class);
        $repository = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(OrderRepository::class);
        $inboxRepository = OrderProcessingStatusMessageHandlerIntegrationTest::getContainer()->get(InboxMessageRepository::class);

        assert($handler instanceof OrderProcessingStatusMessageHandler);
        assert($repository instanceof OrderRepository);
        assert($inboxRepository instanceof InboxMessageRepository);

        $message = new OrderProcessingStatusMessage(
            '019db9fd-ffff-7fe1-88d7-e60e2c100794',
            RabbitMQServiceInterface::MESSAGE_TYPE_ORDER_PROCESSING_STATUS,
            new DateTimeImmutable('2026-04-23T10:58:33+00:00'),
            $order->getId(),
            '019db9fd-eeee-7882-9d08-157918f554c4',
            $product->getId(),
            RabbitMQServiceInterface::ORDER_PROCESSING_STATUS_PROCESSED,
            null,
        );

        $handler($message);
        $handler($message);

        $storedOrder = $repository->find($order->getId());

        self::assertNotNull($storedOrder);
        self::assertSame(OrderServiceInterface::STATUS_PROCESSED, $storedOrder->getOrderStatus());
        self::assertCount(1, $inboxRepository->findAll());
        self::assertSame($message->eventId, $inboxRepository->findAll()[0]->getEventId());
    }

    /**
     * @return array{0: Product, 1: Order}
     */
    private function createOrderFixture(): array
    {
        $product = new Product();
        $product->setId('019db9fd-5141-783b-804e-3f3d8ab184e7');
        $product->setName('Coffee Mug');
        $product->setPrice(12.99);
        $product->setQuantity(5);
        $product->setVersion(1);

        $order = new Order();
        $order->setId(Uuid::v7()->toRfc4122());
        $order->setProduct($product);
        $order->setCustomerName('John Doe');
        $order->setQuantityOrdered(2);
        $order->setOrderStatus(OrderServiceInterface::STATUS_PROCESSING);

        $this->entityManager->persist($product);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return [$product, $order];
    }
}
