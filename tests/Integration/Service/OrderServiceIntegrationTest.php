<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Order;
use App\Entity\Product;
use App\Message\OrderCreatedMessage;
use App\Repository\OrderRepository;
use App\Repository\OutboxMessageRepository;
use App\Repository\ProductRepository;
use App\Service\Api\OrderServiceInterface;
use App\Tests\Integration\DatabaseKernelTestCase;

final class OrderServiceIntegrationTest extends DatabaseKernelTestCase
{
    protected function transportNames(): array
    {
        return ['order_created', 'product_updated', 'product_updated_failed', 'order_processing_status', 'order_processing_status_failed'];
    }

    public function testCreatePersistsOrderAndDispatchesReservationMessageWithoutChangingLocalStock(): void
    {
        $product = $this->createProduct(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            4,
        );
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $service = static::getContainer()->get(OrderServiceInterface::class);
        $orderRepository = static::getContainer()->get(OrderRepository::class);
        $outboxRepository = static::getContainer()->get(OutboxMessageRepository::class);
        $productRepository = static::getContainer()->get(ProductRepository::class);
        $transport = $this->getTransport('order_created');

        \assert($service instanceof OrderServiceInterface);
        \assert($orderRepository instanceof OrderRepository);
        \assert($outboxRepository instanceof OutboxMessageRepository);
        \assert($productRepository instanceof ProductRepository);

        $order = $service->create(new \App\Dto\CreateOrderRequestDto($product, 'John Doe', 2));

        $storedOrder = $orderRepository->find($order->getId());
        $storedProduct = $productRepository->find($product->getId());
        $sentMessages = $transport->getSent();

        self::assertNotNull($storedOrder);
        self::assertInstanceOf(Order::class, $storedOrder);
        self::assertSame(OrderServiceInterface::STATUS_PROCESSING, $storedOrder->getOrderStatus());
        self::assertSame(5, $storedProduct?->getQuantity());
        self::assertCount(1, $sentMessages);
        self::assertCount(1, $outboxRepository->findAll());
        self::assertSame('published', $outboxRepository->findAll()[0]->getStatus());
        self::assertCount(0, $outboxRepository->findCreatedOrdered());
        self::assertInstanceOf(OrderCreatedMessage::class, $sentMessages[0]->getMessage());

        /** @var OrderCreatedMessage $message */
        $message = $sentMessages[0]->getMessage();

        self::assertSame($order->getId(), $message->orderId);
        self::assertSame($product->getId(), $message->productId);
        self::assertSame(2, $message->quantityOrdered);
        self::assertSame(4, $message->expectedProductVersion);
    }

    private function createProduct(string $id, string $name, float $price, int $quantity, int $version): Product
    {
        $product = new Product();
        $product->setId($id);
        $product->setName($name);
        $product->setPrice($price);
        $product->setQuantity($quantity);
        $product->setVersion($version);

        return $product;
    }
}
