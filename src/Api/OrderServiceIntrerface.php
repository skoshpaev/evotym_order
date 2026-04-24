<?php

declare(strict_types=1);

namespace App\Api;

use App\Dto\CreateOrderRequestDto;
use App\Entity\Order;

interface OrderServiceIntrerface
{
    public const CUSTOMER_NAME_MAX_LENGTH = 255;

    public const STATUS_PROCESSING = 'Processing';
    public const STATUS_PROCESSED = 'Processed';
    public const STATUS_FAILED = 'Failed';

    public function create(CreateOrderRequestDto $createOrderRequestDto): Order;
}
