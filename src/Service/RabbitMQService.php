<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Message\OrderCreatedMessage;
use App\Message\ProductUpdatedMessage;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class RabbitMQService implements RabbitMQServiceInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function productUpdated(ProductUpdatedMessage $message): void
    {
        $product = $this->productRepository->find($message->id);

        if ($product === null) {
            $product = Product::create($message->id, $message->name, $message->price, $message->quantity);
            $this->entityManager->persist($product);
        } else {
            $product->sync($message->name, $message->price, $message->quantity);
        }

        $this->entityManager->flush();
    }

    public function orderCreated(OrderCreatedMessage $message): void
    {
        $this->messageBus->dispatch($message);
    }
}
