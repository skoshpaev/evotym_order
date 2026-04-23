<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Message\OrderCreatedMessage;
use App\Message\ProductUpdatedMessage;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class RabbitMQService implements RabbitMQServiceInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IntegrationEventLogger $integrationEventLogger,
    ) {
    }

    public function productUpdated(ProductUpdatedMessage $message): void
    {
        $this->assertProductUpdatedMessageIsValid($message);

        $product = $this->productRepository->find($message->id);

        if ($product !== null && $this->isOldProductMessage($product, $message)) {
            $this->integrationEventLogger->warning(
                'Product update was skipped in order service because a stale event was received.',
                [
                    'eventId' => $message->eventId,
                    'productId' => $message->id,
                    'createdAt' => $message->createdAt->format(DATE_ATOM),
                ],
            );

            return;
        }

        if ($product === null) {
            $product = Product::create($message->id, $message->name, $message->price, $message->quantity, $message->version);
            $this->entityManager->persist($product);
        } else {
            $product->sync($message->name, $message->price, $message->quantity, $message->version);
        }

        $product->markProductEventProcessed($message->createdAt);
        $this->entityManager->flush();
    }

    public function orderCreated(OrderCreatedMessage $message): void
    {
        $this->messageBus->dispatch($message);
    }

    private function isOldProductMessage(Product $product, ProductUpdatedMessage $message): bool
    {
        $lastProductEventAt = $product->getLastProductEventAt();

        return $lastProductEventAt !== null && $message->createdAt <= $lastProductEventAt;
    }

    private function assertProductUpdatedMessageIsValid(ProductUpdatedMessage $message): void
    {
        if ($message->quantity < 0) {
            throw new UnrecoverableMessageHandlingException('Product quantity must be greater than or equal to zero.');
        }

        if ($message->version <= 0) {
            throw new UnrecoverableMessageHandlingException('Product version must be greater than zero.');
        }
    }
}
