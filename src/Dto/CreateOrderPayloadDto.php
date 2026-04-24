<?php

declare(strict_types=1);

namespace App\Dto;

use App\Service\Api\OrderServiceInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderPayloadDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $productId,
        #[Assert\NotBlank]
        #[Assert\Length(max: OrderServiceInterface::CUSTOMER_NAME_MAX_LENGTH)]
        public readonly string $customerName,
        #[Assert\Positive]
        public readonly int $quantityOrdered,
    ) {
    }
}
