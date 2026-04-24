<?php

declare(strict_types=1);

namespace App\Messenger;

use App\Message\OrderCreatedMessage;
use App\Message\OrderProcessingStatusMessage;
use App\Message\ProductUpdatedMessage;
use App\Service\Api\RabbitMQServiceInterface;
use DateTimeImmutable;
use Exception;
use JsonException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class EventMessageSerializer implements SerializerInterface
{
    /**
     * @throws Exception
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        $headers = $encodedEnvelope['headers'] ?? [];
        $type = $headers['type'] ?? null;

        if (!is_string($type) || $type === '') {
            throw new MessageDecodingFailedException('Missing message type header.');
        }

        $body = $encodedEnvelope['body'] ?? '';

        if (!is_string($body)) {
            throw new MessageDecodingFailedException('Message body must be a JSON string.');
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MessageDecodingFailedException(
                sprintf('Could not decode message "%s".', $type), 0, $exception,
            );
        }

        if (!is_array($payload)) {
            throw new MessageDecodingFailedException('Message payload must decode to an object.');
        }

        $message = match ($type) {
            ProductUpdatedMessage::class => $this->denormalizeProductUpdatedMessage($payload),
            OrderCreatedMessage::class => $this->denormalizeOrderCreatedMessage($payload),
            OrderProcessingStatusMessage::class => $this->denormalizeOrderProcessingStatusMessage($payload),
            default => throw new MessageDecodingFailedException(sprintf('Unsupported message type "%s".', $type)),
        };

        return new Envelope($message);
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();

        try {
            $body = json_encode($this->normalizeMessage($message), JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MessageDecodingFailedException(
                sprintf('Could not encode message "%s".', $message::class), 0, $exception,
            );
        }

        return [
            'body'    => $body,
            'headers' => [
                'type'         => $message::class,
                'Content-Type' => 'application/json',
            ],
        ];
    }

    /**
     * @throws Exception
     *
     * @param array<string, mixed> $payload
     */
    private function denormalizeProductUpdatedMessage(array $payload): ProductUpdatedMessage
    {
        return new ProductUpdatedMessage(
            $this->optionalString($payload, 'eventId') ?? Uuid::v7()->toRfc4122(),
            $this->optionalString($payload, 'type') ?? RabbitMQServiceInterface::MESSAGE_TYPE_PRODUCT_UPDATED,
            $this->optionalDateTime($payload, 'createdAt') ?? new DateTimeImmutable(),
            $this->requireString($payload, 'id'),
            $this->requireString($payload, 'name'),
            $this->requireFloat($payload, 'price'),
            $this->requireInt($payload, 'quantity'),
            $this->optionalInt($payload, 'version') ?? 1,
        );
    }

    /**
     * @throws Exception
     *
     * @param array<string, mixed> $payload
     */
    private function denormalizeOrderCreatedMessage(array $payload): OrderCreatedMessage
    {
        return new OrderCreatedMessage(
            $this->optionalString($payload, 'eventId') ?? Uuid::v7()->toRfc4122(),
            $this->optionalString($payload, 'type') ?? RabbitMQServiceInterface::MESSAGE_TYPE_ORDER_CREATED,
            $this->optionalDateTime($payload, 'createdAt') ?? new DateTimeImmutable(),
            $this->optionalString($payload, 'orderId') ??
            $this->optionalString($payload, 'eventId') ?? Uuid::v7()->toRfc4122(),
            $this->requireString($payload, 'productId'),
            $this->optionalInt($payload, 'quantityOrdered') ?? $this->requireInt($payload, 'quantity'),
            $this->optionalInt($payload, 'expectedProductVersion') ?? 1,
        );
    }

    /**
     * @throws Exception
     *
     * @param array<string, mixed> $payload
     */
    private function denormalizeOrderProcessingStatusMessage(array $payload): OrderProcessingStatusMessage
    {
        return new OrderProcessingStatusMessage(
            $this->optionalString($payload, 'eventId') ?? Uuid::v7()->toRfc4122(),
            $this->optionalString($payload, 'type') ?? RabbitMQServiceInterface::MESSAGE_TYPE_ORDER_PROCESSING_STATUS,
            $this->optionalDateTime($payload, 'createdAt') ?? new DateTimeImmutable(),
            $this->optionalString($payload, 'orderId') ?? $this->requireString($payload, 'orderEventId'),
            $this->requireString($payload, 'orderEventId'),
            $this->requireString($payload, 'productId'),
            $this->requireString($payload, 'status'),
            $this->optionalString($payload, 'error'),
        );
    }

    /**
     * @return array<string, scalar|null>
     */
    private function normalizeMessage(object $message): array
    {
        return match (true) {
            $message instanceof ProductUpdatedMessage => [
                'eventId'   => $message->eventId,
                'type'      => $message->type,
                'createdAt' => $message->createdAt->format(DATE_ATOM),
                'id'        => $message->id,
                'name'      => $message->name,
                'price'     => $message->price,
                'quantity'  => $message->quantity,
                'version'   => $message->version,
            ],
            $message instanceof OrderCreatedMessage => [
                'eventId'                => $message->eventId,
                'type'                   => $message->type,
                'createdAt'              => $message->createdAt->format(DATE_ATOM),
                'orderId'                => $message->orderId,
                'productId'              => $message->productId,
                'quantityOrdered'        => $message->quantityOrdered,
                'expectedProductVersion' => $message->expectedProductVersion,
            ],
            $message instanceof OrderProcessingStatusMessage => [
                'eventId'      => $message->eventId,
                'type'         => $message->type,
                'createdAt'    => $message->createdAt->format(DATE_ATOM),
                'orderId'      => $message->orderId,
                'orderEventId' => $message->orderEventId,
                'productId'    => $message->productId,
                'status'       => $message->status,
                'error'        => $message->error,
            ],
            default => throw new MessageDecodingFailedException(
                sprintf('Unsupported message class "%s".', $message::class),
            ),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireString(array $payload, string $field): string
    {
        if (!isset($payload[$field]) || !is_string($payload[$field])) {
            throw new MessageDecodingFailedException(sprintf('Field "%s" must be a string.', $field));
        }

        return $payload[$field];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private function requireFloat(array $payload, string $field): float
    {
        if (!isset($payload[$field]) || (!is_float($payload[$field]) && !is_int($payload[$field]))) {
            throw new MessageDecodingFailedException(sprintf('Field "%s" must be numeric.', $field));
        }

        return (float)$payload[$field];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private function requireInt(array $payload, string $field): int
    {
        if (!isset($payload[$field]) || !is_int($payload[$field])) {
            throw new MessageDecodingFailedException(sprintf('Field "%s" must be an integer.', $field));
        }

        return $payload[$field];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalString(array $payload, string $field): ?string
    {
        $value = $payload[$field] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalInt(array $payload, string $field): ?int
    {
        $value = $payload[$field] ?? null;

        return is_int($value) ? $value : null;
    }

    /**
     * @throws Exception
     *
     * @param array<string, mixed> $payload
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private function optionalDateTime(array $payload, string $field): ?DateTimeImmutable
    {
        $value = $this->optionalString($payload, $field);

        return $value === null ? null : new DateTimeImmutable($value);
    }
}
