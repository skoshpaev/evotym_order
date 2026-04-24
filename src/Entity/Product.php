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

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
        $this->price = self::normalizePrice($price);
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = self::normalizeQuantity($quantity);
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function getLastProductEventAt(): ?DateTimeImmutable
    {
        return $this->lastProductEventAt;
    }

    public function setLastProductEventAt(?DateTimeImmutable $lastProductEventAt): void
    {
        $this->lastProductEventAt = $lastProductEventAt;
    }
}
