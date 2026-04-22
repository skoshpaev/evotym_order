<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateOrderRequestDto;
use App\Entity\Order;

interface OrderServiceInterface
{
    public function create(CreateOrderRequestDto $createOrderRequestDto): Order;
}
