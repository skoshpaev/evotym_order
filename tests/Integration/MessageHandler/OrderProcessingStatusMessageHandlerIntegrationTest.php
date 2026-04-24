<?php

declare(strict_types=1);

namespace App\Tests\Integration\MessageHandler;

use App\Entity\Order;
use App\Entity\Product;
use App\Message\OrderProcessingStatusMessage;
use App\MessageHandler\OrderProcessingStatusMessageHandler;
use App\Repository\InboxMessageRepository;
use App\Repository\OrderRepository;
use App\Tests\Integration\DatabaseKernelTestCase;

final class OrderProcessingStatusMessageHandlerIntegrationTest extends DatabaseKernelTestCase
{
    protected function transportNames(): array
    {
        return ['order_created', 'product_updated', 'product_updated_failed', 'order_processing_status', 'order_processing_status_failed'];
    }

    public function testProcessedStatusMarksOrderAsProcessed(): void
    {
        $product = Product::create(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            1,
        );
        $order = Order::create($product, 'John Doe', 2);

        $this->entityManager->persist($product);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $handler = static::getContainer()->get(OrderProcessingStatusMessageHandler::class);
        $repository = static::getContainer()->get(OrderRepository::class);
        $inboxRepository = static::getContainer()->get(InboxMessageRepository::class);

        \assert($handler instanceof OrderProcessingStatusMessageHandler);
        \assert($repository instanceof OrderRepository);
        \assert($inboxRepository instanceof InboxMessageRepository);

        $message = new OrderProcessingStatusMessage(
            '019db9fd-a933-785a-9485-247a36155e3f',
            OrderProcessingStatusMessage::TYPE,
            new \DateTimeImmutable('2026-04-23T10:58:22+00:00'),
            $order->getId(),
            '019db9fd-a82a-7e90-a13c-ebb0e48c00b2',
            $product->getId(),
            OrderProcessingStatusMessage::STATUS_PROCESSED,
            null,
        );

        $handler($message);

        $storedOrder = $repository->find($order->getId());
        $inboxMessage = $inboxRepository->find($message->eventId);

        self::assertNotNull($storedOrder);
        self::assertSame(Order::STATUS_PROCESSED, $storedOrder->getOrderStatus());
        self::assertNotNull($storedOrder->getLastProcessingStatusEventAt());
        self::assertNotNull($inboxMessage);
        self::assertSame('processed', $inboxMessage->getStatus());
    }

    public function testFailedStatusMarksOrderAsFailed(): void
    {
        $product = Product::create(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            1,
        );
        $order = Order::create($product, 'John Doe', 2);

        $this->entityManager->persist($product);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $handler = static::getContainer()->get(OrderProcessingStatusMessageHandler::class);
        $repository = static::getContainer()->get(OrderRepository::class);
        $inboxRepository = static::getContainer()->get(InboxMessageRepository::class);

        \assert($handler instanceof OrderProcessingStatusMessageHandler);
        \assert($repository instanceof OrderRepository);
        \assert($inboxRepository instanceof InboxMessageRepository);

        $message = new OrderProcessingStatusMessage(
            '019db9fd-d456-7fe1-88d7-e60e2c100794',
            OrderProcessingStatusMessage::TYPE,
            new \DateTimeImmutable('2026-04-23T10:58:33+00:00'),
            $order->getId(),
            '019db9fd-d2ea-7882-9d08-157918f554c4',
            $product->getId(),
            OrderProcessingStatusMessage::STATUS_FAILED,
            'Product version mismatch.',
        );

        $handler($message);

        $storedOrder = $repository->find($order->getId());
        $inboxMessage = $inboxRepository->find($message->eventId);

        self::assertNotNull($storedOrder);
        self::assertSame(Order::STATUS_FAILED, $storedOrder->getOrderStatus());
        self::assertNotNull($storedOrder->getLastProcessingStatusEventAt());
        self::assertNotNull($inboxMessage);
        self::assertSame('processed', $inboxMessage->getStatus());
    }

    public function testDuplicateStatusEventIsIgnoredByInbox(): void
    {
        $product = Product::create(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            1,
        );
        $order = Order::create($product, 'John Doe', 2);

        $this->entityManager->persist($product);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $handler = static::getContainer()->get(OrderProcessingStatusMessageHandler::class);
        $repository = static::getContainer()->get(OrderRepository::class);
        $inboxRepository = static::getContainer()->get(InboxMessageRepository::class);

        \assert($handler instanceof OrderProcessingStatusMessageHandler);
        \assert($repository instanceof OrderRepository);
        \assert($inboxRepository instanceof InboxMessageRepository);

        $message = new OrderProcessingStatusMessage(
            '019db9fd-ffff-7fe1-88d7-e60e2c100794',
            OrderProcessingStatusMessage::TYPE,
            new \DateTimeImmutable('2026-04-23T10:58:33+00:00'),
            $order->getId(),
            '019db9fd-eeee-7882-9d08-157918f554c4',
            $product->getId(),
            OrderProcessingStatusMessage::STATUS_PROCESSED,
            null,
        );

        $handler($message);
        $handler($message);

        $storedOrder = $repository->find($order->getId());

        self::assertNotNull($storedOrder);
        self::assertSame(Order::STATUS_PROCESSED, $storedOrder->getOrderStatus());
        self::assertCount(1, $inboxRepository->findAll());
        self::assertSame($message->eventId, $inboxRepository->findAll()[0]->getEventId());
    }
}
