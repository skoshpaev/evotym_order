<?php

declare(strict_types=1);

namespace App\Tests\Unit\Messenger;

use App\Api\RabbitMQServiceIntrerface;
use App\Message\OrderCreatedMessage;
use App\Message\ProductUpdatedMessage;
use App\Messenger\EventMessageSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

final class EventMessageSerializerTest extends TestCase
{
    public function testEncodeAndDecodePreservesProductVersion(): void
    {
        $serializer = new EventMessageSerializer();
        $message = new ProductUpdatedMessage(
            '019db9fd-a933-785a-9485-247a36155e3f',
            RabbitMQServiceIntrerface::MESSAGE_TYPE_PRODUCT_UPDATED,
            new \DateTimeImmutable('2026-04-23T10:58:22+00:00'),
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            3,
        );

        $encoded = $serializer->encode(new Envelope($message));
        $decoded = $serializer->decode($encoded)->getMessage();

        self::assertInstanceOf(ProductUpdatedMessage::class, $decoded);
        self::assertSame(3, $decoded->version);
        self::assertSame(5, $decoded->quantity);
    }

    public function testDecodeSupportsLegacyOrderCreatedPayload(): void
    {
        $serializer = new EventMessageSerializer();
        $encodedEnvelope = [
            'body' => json_encode([
                'eventId' => '019db9fd-a82a-7e90-a13c-ebb0e48c00b2',
                'type' => RabbitMQServiceIntrerface::MESSAGE_TYPE_ORDER_CREATED,
                'createdAt' => '2026-04-23T10:58:22+00:00',
                'productId' => '019db9fd-5141-783b-804e-3f3d8ab184e7',
                'quantity' => 2,
            ], JSON_THROW_ON_ERROR),
            'headers' => [
                'type' => OrderCreatedMessage::class,
                'Content-Type' => 'application/json',
            ],
        ];

        $decoded = $serializer->decode($encodedEnvelope)->getMessage();

        self::assertInstanceOf(OrderCreatedMessage::class, $decoded);
        self::assertSame('019db9fd-a82a-7e90-a13c-ebb0e48c00b2', $decoded->orderId);
        self::assertSame(2, $decoded->quantityOrdered);
        self::assertSame(1, $decoded->expectedProductVersion);
    }
}
