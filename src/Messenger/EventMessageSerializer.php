<?php

declare(strict_types=1);

namespace App\Messenger;

use App\Message\OrderCreatedMessage;
use App\Message\ProductUpdatedMessage;
use JsonException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class EventMessageSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $headers = $encodedEnvelope['headers'] ?? [];
        $type = $headers['type'] ?? null;

        if (!\is_string($type) || $type === '') {
            throw new MessageDecodingFailedException('Missing message type header.');
        }

        $body = $encodedEnvelope['body'] ?? '';

        if (!\is_string($body)) {
            throw new MessageDecodingFailedException('Message body must be a JSON string.');
        }

        try {
            /** @var mixed $payload */
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MessageDecodingFailedException(
                sprintf('Could not decode message "%s".', $type),
                0,
                $exception,
            );
        }

        if (!\is_array($payload)) {
            throw new MessageDecodingFailedException('Message payload must decode to an object.');
        }

        $message = match ($type) {
            ProductUpdatedMessage::class => $this->denormalizeProductUpdatedMessage($payload),
            OrderCreatedMessage::class => $this->denormalizeOrderCreatedMessage($payload),
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
                sprintf('Could not encode message "%s".', $message::class),
                0,
                $exception,
            );
        }

        return [
            'body' => $body,
            'headers' => [
                'type' => $message::class,
                'Content-Type' => 'application/json',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function denormalizeProductUpdatedMessage(array $payload): ProductUpdatedMessage
    {
        $id = $this->requireString($payload, 'id');
        $name = $this->requireString($payload, 'name');
        $price = $this->requireFloat($payload, 'price');
        $quantity = $this->requireInt($payload, 'quantity');

        return new ProductUpdatedMessage($id, $name, $price, $quantity);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function denormalizeOrderCreatedMessage(array $payload): OrderCreatedMessage
    {
        $productId = $this->requireString($payload, 'productId');
        $quantity = $this->requireInt($payload, 'quantity');

        return new OrderCreatedMessage($productId, $quantity);
    }

    /**
     * @return array<string, scalar>
     */
    private function normalizeMessage(object $message): array
    {
        return match (true) {
            $message instanceof ProductUpdatedMessage => [
                'id' => $message->id,
                'name' => $message->name,
                'price' => $message->price,
                'quantity' => $message->quantity,
            ],
            $message instanceof OrderCreatedMessage => [
                'productId' => $message->productId,
                'quantity' => $message->quantity,
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
        if (!isset($payload[$field]) || !\is_string($payload[$field])) {
            throw new MessageDecodingFailedException(sprintf('Field "%s" must be a string.', $field));
        }

        return $payload[$field];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireFloat(array $payload, string $field): float
    {
        if (!isset($payload[$field]) || (!\is_float($payload[$field]) && !\is_int($payload[$field]))) {
            throw new MessageDecodingFailedException(sprintf('Field "%s" must be numeric.', $field));
        }

        return (float) $payload[$field];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireInt(array $payload, string $field): int
    {
        if (!isset($payload[$field]) || !\is_int($payload[$field])) {
            throw new MessageDecodingFailedException(sprintf('Field "%s" must be an integer.', $field));
        }

        return $payload[$field];
    }
}
