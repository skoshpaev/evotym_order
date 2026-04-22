<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product
{
    public const NAME_MAX_LENGTH = 255;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $id;

    #[ORM\Column(length: self::NAME_MAX_LENGTH)]
    private string $name;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: Types::INTEGER)]
    private int $quantity;

    private function __construct(string $id, string $name, float $price, int $quantity)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = self::normalizePrice($price);
        $this->quantity = $quantity;
    }

    public static function create(string $id, string $name, float $price, int $quantity): self
    {
        return new self($id, $name, $price, $quantity);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return (float) $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function decreaseQuantity(int $quantity): void
    {
        if ($quantity > $this->quantity) {
            throw new \LogicException('Ordered quantity exceeds available stock.');
        }

        $this->quantity -= $quantity;
    }

    public function sync(string $name, float $price, int $quantity): void
    {
        $this->name = $name;
        $this->price = self::normalizePrice($price);
        $this->quantity = $quantity;
    }

    private static function normalizePrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }
}
