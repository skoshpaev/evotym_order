<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Evotym\SharedBundle\Entity\AbstractProduct;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product extends AbstractProduct
{
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\Column(name: 'last_product_event_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastProductEventAt = null;

    private function __construct(string $id, string $name, float $price, int $quantity, int $version)
    {
        $this->initializeProduct($id, $name, $price, $quantity);
        $this->version = self::normalizeVersion($version);
    }

    public static function create(string $id, string $name, float $price, int $quantity, int $version): self
    {
        return new self($id, $name, $price, $quantity, $version);
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function sync(string $name, float $price, int $quantity, int $version): void
    {
        $this->syncProductData($name, $price, $quantity);
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

    private static function normalizeVersion(int $version): int
    {
        if ($version <= 0) {
            throw new \InvalidArgumentException('Version must be greater than zero.');
        }

        return $version;
    }
}
