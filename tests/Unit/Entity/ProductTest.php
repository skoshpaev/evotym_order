<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testSettersStoreSnapshotFields(): void
    {
        $product = new Product();
        $product->setId('019db9fd-5141-783b-804e-3f3d8ab184e7');
        $product->setName('Coffee Mug XL');
        $product->setPrice(15.50);
        $product->setQuantity(7);
        $product->setVersion(2);

        self::assertSame('019db9fd-5141-783b-804e-3f3d8ab184e7', $product->getId());
        self::assertSame('Coffee Mug XL', $product->getName());
        self::assertSame(15.50, $product->getPrice());
        self::assertSame(7, $product->getQuantity());
        self::assertSame(2, $product->getVersion());
    }

    public function testLastProductEventAtCanBeUpdated(): void
    {
        $product = new Product();
        $createdAt = new DateTimeImmutable('2026-04-24T10:00:00+00:00');

        $product->setLastProductEventAt($createdAt);

        self::assertSame($createdAt, $product->getLastProductEventAt());
    }
}
