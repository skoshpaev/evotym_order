<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Message\OrderCreatedMessage;
use App\Message\ProductUpdatedMessage;
use App\Repository\OutboxMessageRepository;
use App\Repository\ProductRepository;
use App\Service\RabbitMQServiceInterface;
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

        $message = OrderCreatedMessage::fromPayload(
            '019dbb31-0a4c-76cd-ac12-a30c32589541',
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            2,
            4,
            '019dbb31-0f4b-7b31-a685-e35f6d1d40ac',
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

        \assert($service instanceof RabbitMQServiceInterface);
        \assert($repository instanceof ProductRepository);

        $service->productUpdated(ProductUpdatedMessage::fromPayload(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            2,
            '019db9fd-a933-785a-9485-247a36155e3f',
            ProductUpdatedMessage::TYPE,
            new \DateTimeImmutable('2026-04-23T10:58:22+00:00'),
        ));

        $product = $repository->find('019db9fd-5141-783b-804e-3f3d8ab184e7');

        self::assertNotNull($product);
        self::assertSame('Coffee Mug', $product->getName());
        self::assertSame(5, $product->getQuantity());
        self::assertSame(2, $product->getVersion());
    }

    public function testProductUpdatedIgnoresStaleMessages(): void
    {
        $service = static::getContainer()->get(RabbitMQServiceInterface::class);
        $repository = static::getContainer()->get(ProductRepository::class);

        \assert($service instanceof RabbitMQServiceInterface);
        \assert($repository instanceof ProductRepository);

        $service->productUpdated(ProductUpdatedMessage::fromPayload(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            2,
            '019db9fd-a933-785a-9485-247a36155e3f',
            ProductUpdatedMessage::TYPE,
            new \DateTimeImmutable('2026-04-23T10:58:22+00:00'),
        ));

        $service->productUpdated(ProductUpdatedMessage::fromPayload(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Stale Mug',
            99.99,
            99,
            1,
            '019db9fd-bbbb-785a-9485-247a36155e3f',
            ProductUpdatedMessage::TYPE,
            new \DateTimeImmutable('2026-04-23T10:57:22+00:00'),
        ));

        $product = $repository->find('019db9fd-5141-783b-804e-3f3d8ab184e7');

        self::assertNotNull($product);
        self::assertSame('Coffee Mug', $product->getName());
        self::assertSame(5, $product->getQuantity());
        self::assertSame(2, $product->getVersion());
    }
}
