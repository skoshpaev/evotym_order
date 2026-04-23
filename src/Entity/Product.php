<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use DateTimeImmutable;
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

    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\Column(name: 'last_product_event_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastProductEventAt = null;

    private function __construct(string $id, string $name, float $price, int $quantity, int $version)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = self::normalizePrice($price);
        $this->quantity = self::normalizeQuantity($quantity);
        $this->version = self::normalizeVersion($version);
    }

    public static function create(string $id, string $name, float $price, int $quantity, int $version): self
    {
        return new self($id, $name, $price, $quantity, $version);
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

    public function getVersion(): int
    {
        return $this->version;
    }

    public function decreaseQuantity(int $quantity): void
    {
        if ($quantity > $this->quantity) {
            throw new \LogicException('Ordered quantity exceeds available stock.');
        }

        $this->quantity -= $quantity;
    }

    public function sync(string $name, float $price, int $quantity, int $version): void
    {
        $this->name = $name;
        $this->price = self::normalizePrice($price);
        $this->quantity = self::normalizeQuantity($quantity);
        $this->version = self::normalizeVersion($version);
    }

    public function getLastProductEventAt(): ?DateTimeImmutable
    {
        return $this->lastProductEventAt;
    }

    public function markProductEventProcessed(DateTimeImmutable $createdAt): void
    {
        $this->lastProductEventAt = $createdAt;
    }

    private static function normalizePrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }

    private static function normalizeQuantity(int $quantity): int
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be greater than or equal to zero.');
        }

        return $quantity;
    }

    private static function normalizeVersion(int $version): int
    {
        if ($version <= 0) {
            throw new \InvalidArgumentException('Version must be greater than zero.');
        }

        return $version;
    }
}
