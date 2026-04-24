<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\OrderResponseDto;
use App\Entity\Order;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{id}', name: 'order_show', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
final class ShowOrderController
{
    public function __invoke(Order $order): JsonResponse
    {
        return new JsonResponse(OrderResponseDto::fromEntity($order)->toArray());
    }
}
