<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\InboxMessage;
use App\Entity\Order;
use App\Entity\OutboxMessage;
use App\Entity\Product;
use App\Message\OrderCreatedMessage;
use App\Message\OrderProcessingStatusMessage;
use App\Message\ProductUpdatedMessage;
use App\Repository\InboxMessageRepository;
use App\Repository\OrderRepository;
use App\Repository\OutboxMessageRepository;
use App\Repository\ProductRepository;
use App\Service\Api\IntegrationEventLoggerServiceInterface;
use App\Service\Api\OrderServiceInterface;
use App\Service\Api\RabbitMQServiceInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final class RabbitMQService implements RabbitMQServiceInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ProductRepository $productRepository,
        private readonly OrderRepository $orderRepository,
        private readonly InboxMessageRepository $inboxMessageRepository,
        private readonly OutboxMessageRepository $outboxMessageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IntegrationEventLoggerServiceInterface $integrationEventLogger,
    ) {
    }

    public function productUpdated(ProductUpdatedMessage $message): void
    {
        if ($this->inboxMessageRepository->find($message->eventId) !== null) {
            return;
        }

        $this->assertProductUpdatedMessageIsValid($message);

        $product = $this->productRepository->find($message->id);

        if ($product !== null && $this->isOldProductMessage($product, $message)) {
            $this->storeProductUpdatedInboxResult($message, $product->getId(), self::INBOX_STATUS_IGNORED);
            $this->integrationEventLogger->warning(
                'Product update was skipped in order service because a stale event was received.',
                [
                    'eventId'   => $message->eventId,
                    'productId' => $message->id,
                    'createdAt' => $message->createdAt->format(DATE_ATOM),
                ],
            );
            $this->entityManager->flush();

            return;
        }

        if ($product === null) {
            $product = new Product();
            $product->setId($message->id);
            $this->entityManager->persist($product);
        }

        $product->setName($message->name);
        $product->setPrice($message->price);
        $product->setQuantity($message->quantity);
        $product->setVersion($message->version);
        $product->setLastProductEventAt($message->createdAt);

        $this->storeProductUpdatedInboxResult($message, $product->getId(), self::INBOX_STATUS_PROCESSED);
        $this->entityManager->flush();
    }

    public function orderProcessingStatus(OrderProcessingStatusMessage $message): void
    {
        if ($this->inboxMessageRepository->find($message->eventId) !== null) {
            return;
        }

        $context = [
            'eventId'      => $message->eventId,
            'orderId'      => $message->orderId,
            'orderEventId' => $message->orderEventId,
            'productId'    => $message->productId,
            'status'       => $message->status,
            'createdAt'    => $message->createdAt->format(DATE_ATOM),
            'error'        => $message->error,
        ];

        $order = $this->orderRepository->find($message->orderId);

        if ($order === null) {
            $this->storeOrderProcessingStatusInboxResult($message, self::INBOX_STATUS_FAILED);
            $this->entityManager->flush();
            $this->integrationEventLogger->warning(
                'Order processing status was received for a missing order.',
                $context,
            );

            return;
        }

        if ($this->isOldOrderProcessingStatusMessage($order, $message)) {
            $this->storeOrderProcessingStatusInboxResult($message, self::INBOX_STATUS_IGNORED);
            $this->entityManager->flush();
            $this->integrationEventLogger->warning(
                'Order processing status was ignored because a stale event was received.',
                $context + ['currentOrderStatus' => $order->getOrderStatus()],
            );

            return;
        }

        if ($order->getOrderStatus() !== OrderServiceInterface::STATUS_PROCESSING) {
            $this->storeOrderProcessingStatusInboxResult($message, self::INBOX_STATUS_IGNORED);
            $this->entityManager->flush();
            $this->integrationEventLogger->warning(
                'Order processing status was ignored because the order is already terminal.',
                $context + ['currentOrderStatus' => $order->getOrderStatus()],
            );

            return;
        }

        if ($message->status === self::ORDER_PROCESSING_STATUS_FAILED) {
            $order->setOrderStatus(OrderServiceInterface::STATUS_FAILED);
            $order->setLastProcessingStatusEventAt($message->createdAt);
            $this->storeOrderProcessingStatusInboxResult($message, self::INBOX_STATUS_PROCESSED);
            $this->entityManager->flush();
            $this->integrationEventLogger->error(
                'Product service failed to process order event.',
                $context,
            );

            return;
        }

        $order->setOrderStatus(OrderServiceInterface::STATUS_PROCESSED);
        $order->setLastProcessingStatusEventAt($message->createdAt);
        $this->storeOrderProcessingStatusInboxResult($message, self::INBOX_STATUS_PROCESSED);
        $this->entityManager->flush();
        $this->integrationEventLogger->info(
            'Product service processed order event.',
            $context,
        );
    }

    public function orderCreated(OrderCreatedMessage $message): string
    {
        $this->entityManager->persist(
            $this->createOutboxMessage(
                $message->eventId,
                $this->normalizeOrderCreatedMessage($message),
                $message->productId,
                $message->type,
                $message->createdAt,
            )
        );

        return $message->eventId;
    }

    public function publishOutboxMessage(string $eventId): bool
    {
        $outboxMessage = $this->outboxMessageRepository->find($eventId);

        if (!$outboxMessage instanceof OutboxMessage || $outboxMessage->getStatus() !== self::OUTBOX_STATUS_CREATED) {
            return false;
        }

        return $this->publishMessage($outboxMessage);
    }

    public function publishPendingOutbox(): int
    {
        $published = 0;

        foreach ($this->outboxMessageRepository->findCreatedOrdered() as $outboxMessage) {
            if ($this->publishMessage($outboxMessage)) {
                ++$published;
            }
        }

        return $published;
    }

    private function isOldProductMessage(Product $product, ProductUpdatedMessage $message): bool
    {
        $lastProductEventAt = $product->getLastProductEventAt();

        return $lastProductEventAt !== null && $message->createdAt <= $lastProductEventAt;
    }

    private function isOldOrderProcessingStatusMessage(Order $order, OrderProcessingStatusMessage $message): bool
    {
        $lastProcessingStatusEventAt = $order->getLastProcessingStatusEventAt();

        return $lastProcessingStatusEventAt !== null && $message->createdAt <= $lastProcessingStatusEventAt;
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

    private function storeProductUpdatedInboxResult(
        ProductUpdatedMessage $message,
        ?string $productId,
        string $status,
    ): void {
        $this->entityManager->persist(
            $this->createInboxMessage(
                $message->eventId,
                $this->normalizeProductUpdatedMessage($message),
                $productId,
                $message->type,
                $status,
                $message->createdAt,
            )
        );
    }

    private function storeOrderProcessingStatusInboxResult(
        OrderProcessingStatusMessage $message,
        string $status,
    ): void {
        $this->entityManager->persist(
            $this->createInboxMessage(
                $message->eventId,
                $this->normalizeOrderProcessingStatusMessage($message),
                $message->productId,
                $message->type,
                $status,
                $message->createdAt,
            )
        );
    }

    /**
     * @return array<string, scalar|null>
     */
    private function normalizeOrderCreatedMessage(OrderCreatedMessage $message): array
    {
        return [
            'eventId'                => $message->eventId,
            'type'                   => $message->type,
            'createdAt'              => $message->createdAt->format(DATE_ATOM),
            'orderId'                => $message->orderId,
            'productId'              => $message->productId,
            'quantityOrdered'        => $message->quantityOrdered,
            'expectedProductVersion' => $message->expectedProductVersion,
        ];
    }

    /**
     * @return array<string, scalar|null>
     */
    private function normalizeProductUpdatedMessage(ProductUpdatedMessage $message): array
    {
        return [
            'eventId'   => $message->eventId,
            'type'      => $message->type,
            'createdAt' => $message->createdAt->format(DATE_ATOM),
            'id'        => $message->id,
            'name'      => $message->name,
            'price'     => $message->price,
            'quantity'  => $message->quantity,
            'version'   => $message->version,
        ];
    }

    /**
     * @return array<string, scalar|null>
     */
    private function normalizeOrderProcessingStatusMessage(OrderProcessingStatusMessage $message): array
    {
        return [
            'eventId'      => $message->eventId,
            'type'         => $message->type,
            'createdAt'    => $message->createdAt->format(DATE_ATOM),
            'orderId'      => $message->orderId,
            'orderEventId' => $message->orderEventId,
            'productId'    => $message->productId,
            'status'       => $message->status,
            'error'        => $message->error,
        ];
    }

    /**
     * @throws Exception
     */
    private function denormalizeOutboxMessage(OutboxMessage $outboxMessage): object
    {
        $event = $outboxMessage->getEvent();

        return match ($outboxMessage->getEventType()) {
            self::MESSAGE_TYPE_ORDER_CREATED => new OrderCreatedMessage(
                $this->requireString($event, 'eventId'),
                $this->requireString($event, 'type'),
                $this->requireDateTime($event, 'createdAt'),
                $this->requireString($event, 'orderId'),
                $this->requireString($event, 'productId'),
                $this->requireInt($event, 'quantityOrdered'),
                $this->requireInt($event, 'expectedProductVersion'),
            ),
            default => throw new LogicException(
                sprintf('Unsupported outbox event type "%s".', $outboxMessage->getEventType())
            ),
        };
    }

    private function publishMessage(OutboxMessage $outboxMessage): bool
    {
        try {
            $this->messageBus->dispatch($this->denormalizeOutboxMessage($outboxMessage));
            $outboxMessage->setStatus(self::OUTBOX_STATUS_PUBLISHED);
            $this->entityManager->flush();

            return true;
        } catch (Throwable $exception) {
            $this->integrationEventLogger->warning(
                'Outbox event publication failed and will be retried later.',
                [
                    'eventId'   => $outboxMessage->getEventId(),
                    'eventType' => $outboxMessage->getEventType(),
                    'error'     => $exception->getMessage(),
                ],
            );

            return false;
        }
    }

    /**
     * @param array<string, scalar|null> $event
     */
    private function requireString(array $event, string $field): string
    {
        $value = $event[$field] ?? null;

        if (!is_string($value) || $value === '') {
            throw new LogicException(sprintf('Field "%s" must be a non-empty string.', $field));
        }

        return $value;
    }

    /**
     * @param array<string, scalar|null> $event
     */
    private function requireInt(array $event, string $field): int
    {
        $value = $event[$field] ?? null;

        if (!is_int($value)) {
            throw new LogicException(sprintf('Field "%s" must be an integer.', $field));
        }

        return $value;
    }

    /**
     * @throws Exception
     *
     * @param array<string, scalar|null> $event
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private function requireDateTime(array $event, string $field): DateTimeImmutable
    {
        return new DateTimeImmutable($this->requireString($event, $field));
    }

    /**
     * @param array<string, scalar|null> $event
     */
    private function createInboxMessage(
        string $eventId,
        array $event,
        ?string $productId,
        string $eventType,
        string $status,
        DateTimeImmutable $createdAt,
    ): InboxMessage {
        $inboxMessage = new InboxMessage();
        $inboxMessage->setEventId($eventId);
        $inboxMessage->setEvent($event);
        $inboxMessage->setProductId($productId);
        $inboxMessage->setEventType($eventType);
        $inboxMessage->setStatus($status);
        $inboxMessage->setCreatedAt($createdAt);

        return $inboxMessage;
    }

    /**
     * @param array<string, scalar|null> $event
     */
    private function createOutboxMessage(
        string $eventId,
        array $event,
        ?string $productId,
        string $eventType,
        DateTimeImmutable $createdAt,
    ): OutboxMessage {
        $outboxMessage = new OutboxMessage();
        $outboxMessage->setEventId($eventId);
        $outboxMessage->setEvent($event);
        $outboxMessage->setProductId($productId);
        $outboxMessage->setEventType($eventType);
        $outboxMessage->setStatus(self::OUTBOX_STATUS_CREATED);
        $outboxMessage->setCreatedAt($createdAt);

        return $outboxMessage;
    }
}
