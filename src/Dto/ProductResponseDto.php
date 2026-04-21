<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Product;

final class ProductResponseDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly float $price,
        public readonly int $quantity,
    ) {
    }

    public static function fromEntity(Product $product): self
    {
        return new self(
            $product->getId(),
            $product->getName(),
            $product->getPrice(),
            $product->getQuantity(),
        );
    }

    /**
     * @return array{id: string, name: string, price: float, quantity: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
        ];
    }
}
