<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Order;
use Evotym\SharedBundle\Dto\ProductViewDto;

final class OrderResponseDto
{
    public function __construct(
        public readonly string $orderId,
        public readonly ProductViewDto $product,
        public readonly string $customerName,
        public readonly int $quantityOrdered,
        public readonly string $orderStatus,
    ) {
    }
}
