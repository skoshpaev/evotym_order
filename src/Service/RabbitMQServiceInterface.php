<?php

declare(strict_types=1);

namespace App\Service;

use App\Message\OrderCreatedMessage;
use App\Message\ProductUpdatedMessage;

interface RabbitMQServiceInterface
{
    public function productUpdated(ProductUpdatedMessage $message): void;

    public function orderCreated(OrderCreatedMessage $message): void;
}
