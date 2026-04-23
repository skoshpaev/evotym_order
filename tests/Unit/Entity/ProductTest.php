<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testSyncUpdatesFieldsAndVersion(): void
    {
        $product = Product::create(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            1,
        );

        $product->sync('Coffee Mug XL', 15.50, 7, 2);

        self::assertSame('Coffee Mug XL', $product->getName());
        self::assertSame(15.50, $product->getPrice());
        self::assertSame(7, $product->getQuantity());
        self::assertSame(2, $product->getVersion());
    }

    public function testCreateRejectsNonPositiveVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Version must be greater than zero.');

        Product::create(
            '019db9fd-5141-783b-804e-3f3d8ab184e7',
            'Coffee Mug',
            12.99,
            5,
            0,
        );
    }
}
