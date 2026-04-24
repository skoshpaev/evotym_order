<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Message\OrderCreatedMessage;
use App\Message\ProductUpdatedMessage;
use App\Repository\InboxMessageRepository;
use App\Repository\OutboxMessageRepository;
use App\Repository\ProductRepository;
use App\Service\Api\RabbitMQServiceInterface;
use App\Tests\Integration\DatabaseKernelTestCase;

final class RabbitMQServiceIntegrationTest extends DatabaseKernelTestCase
{
    protected function transportNames(): array
    {
        return ['order_created', 'product_updated', 'product_updated_failed', 'order_processing_status', 'order_processing_status_failed'];
    }

    public function testPublishPendingOutboxDispatchesQueuedOrderEvents(): void
    {
        $service = static::getContainer()->get(RabbitMQServiceInterface::class);
        $repository = static::getContainer()->get(OutboxMessageRepository::class);
        $transport = $this->getTransport('order_created');

        \assert($service instanceof RabbitMQServiceInterface);
        \assert($repository instanceof OutboxMessageRepository);

        $message = new OrderCreatedMessage(
            '019dbb31-0f4b-7b31-a685-e35f6d1d40ac',
            RabbitMQServiceInterface::MESSAGE_TYPE_ORDER_CREATED,
            new \DateTimeImmutable('2026-04-23T10:58:22+00:00'),
            '019dbb31-0a4c-76cd-ac12-a30c32589541',
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            2,
            4,
        );

        $eventId = $service->orderCreated($message);
        $this->entityManager->flush();

        self::assertSame($eventId, $message->eventId);
        self::assertCount(0, $transport->getSent());
        self::assertCount(1, $repository->findCreatedOrdered());
        self::assertSame(1, $service->publishPendingOutbox());
        self::assertCount(1, $transport->getSent());
        self::assertCount(0, $repository->findCreatedOrdered());
    }

    public function testProductUpdatedCreatesLocalProductSnapshot(): void
    {
        $service = static::getContainer()->get(RabbitMQServiceInterface::class);
        $repository = static::getContainer()->get(ProductRepository::class);
        $inboxRepository = static::getContainer()->get(InboxMessageRepository::class);

        \assert($service instanceof RabbitMQServiceInterface);
        \assert($repository instanceof ProductRepository);
        \assert($inboxRepository instanceof InboxMessageRepository);

        $message = new ProductUpdatedMessage(
            '019db9fd-a933-785a-9485-247a36155e3f',
            RabbitMQServiceInterface::MESSAGE_TYPE_PRODUCT_UPDATED,
            new \DateTimeImmutable('2026-04-23T10:58:22+00:00'),
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            2,
        );

        $service->productUpdated($message);

        $product = $repository->find('019db9fd-5141-783b-804e-3f3d8ab184e7');
        $inboxMessage = $inboxRepository->find($message->eventId);

        self::assertNotNull($product);
        self::assertSame('Coffee Mug', $product->getName());
        self::assertSame(5, $product->getQuantity());
        self::assertSame(2, $product->getVersion());
        self::assertNotNull($inboxMessage);
        self::assertSame('processed', $inboxMessage->getStatus());
    }

    public function testProductUpdatedIgnoresStaleMessages(): void
    {
        $service = static::getContainer()->get(RabbitMQServiceInterface::class);
        $repository = static::getContainer()->get(ProductRepository::class);
        $inboxRepository = static::getContainer()->get(InboxMessageRepository::class);

        \assert($service instanceof RabbitMQServiceInterface);
        \assert($repository instanceof ProductRepository);
        \assert($inboxRepository instanceof InboxMessageRepository);

        $service->productUpdated(new ProductUpdatedMessage(
            '019db9fd-a933-785a-9485-247a36155e3f',
            RabbitMQServiceInterface::MESSAGE_TYPE_PRODUCT_UPDATED,
            new \DateTimeImmutable('2026-04-23T10:58:22+00:00'),
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            2,
        ));

        $service->productUpdated(new ProductUpdatedMessage(
            '019db9fd-bbbb-785a-9485-247a36155e3f',
            RabbitMQServiceInterface::MESSAGE_TYPE_PRODUCT_UPDATED,
            new \DateTimeImmutable('2026-04-23T10:57:22+00:00'),
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Stale Mug',
            99.99,
            99,
            1,
        ));

        $product = $repository->find('019db9fd-5141-783b-804e-3f3d8ab184e7');
        $inboxMessage = $inboxRepository->find('019db9fd-bbbb-785a-9485-247a36155e3f');

        self::assertNotNull($product);
        self::assertSame('Coffee Mug', $product->getName());
        self::assertSame(5, $product->getQuantity());
        self::assertSame(2, $product->getVersion());
        self::assertNotNull($inboxMessage);
        self::assertSame('ignored', $inboxMessage->getStatus());
    }

    public function testProductUpdatedIgnoresDuplicateEventId(): void
    {
        $service = static::getContainer()->get(RabbitMQServiceInterface::class);
        $repository = static::getContainer()->get(ProductRepository::class);
        $inboxRepository = static::getContainer()->get(InboxMessageRepository::class);

        \assert($service instanceof RabbitMQServiceInterface);
        \assert($repository instanceof ProductRepository);
        \assert($inboxRepository instanceof InboxMessageRepository);

        $message = new ProductUpdatedMessage(
            '019db9fd-cccc-785a-9485-247a36155e3f',
            RabbitMQServiceInterface::MESSAGE_TYPE_PRODUCT_UPDATED,
            new \DateTimeImmutable('2026-04-23T10:58:22+00:00'),
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            2,
        );

        $service->productUpdated($message);
        $service->productUpdated($message);

        $product = $repository->find('019db9fd-5141-783b-804e-3f3d8ab184e7');
        $inboxMessages = $inboxRepository->findAll();

        self::assertNotNull($product);
        self::assertSame(5, $product->getQuantity());
        self::assertCount(1, $inboxMessages);
        self::assertSame($message->eventId, $inboxMessages[0]->getEventId());
    }
}
