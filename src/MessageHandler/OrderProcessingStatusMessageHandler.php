<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Message\OrderProcessingStatusMessage;
use App\Repository\OrderRepository;
use App\Service\IntegrationEventLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'order_processing_status')]
final class OrderProcessingStatusMessageHandler
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IntegrationEventLogger $integrationEventLogger,
    ) {
    }

    public function __invoke(OrderProcessingStatusMessage $message): void
    {
        $context = [
            'eventId' => $message->eventId,
            'orderId' => $message->orderId,
            'orderEventId' => $message->orderEventId,
            'productId' => $message->productId,
            'status' => $message->status,
            'createdAt' => $message->createdAt->format(DATE_ATOM),
            'error' => $message->error,
        ];

        $order = $this->orderRepository->find($message->orderId);

        if ($order === null) {
            $this->integrationEventLogger->warning(
                'Order processing status was received for a missing order.',
                $context,
            );

            return;
        }

        if ($order->getOrderStatus() !== Order::STATUS_PROCESSING) {
            $this->integrationEventLogger->warning(
                'Order processing status was ignored because the order is already terminal.',
                $context + ['currentOrderStatus' => $order->getOrderStatus()],
            );

            return;
        }

        if ($message->status === OrderProcessingStatusMessage::STATUS_FAILED) {
            $order->markFailed();
            $this->entityManager->flush();
            $this->integrationEventLogger->error(
                'Product service failed to process order event.',
                $context,
            );

            return;
        }

        $order->markProcessed();
        $this->entityManager->flush();
        $this->integrationEventLogger->info(
            'Product service processed order event.',
            $context,
        );
    }
}
