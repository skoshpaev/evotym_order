<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Product;

final class CreateOrderRequestDto
{
    public function __construct(
        public readonly Product $product,
        public readonly string $customerName,
        public readonly int $quantityOrdered,
    ) {
    }
}
