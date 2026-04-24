<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\OrderServiceIntrerface;
use App\Dto\CreateOrderRequestDto;
use App\Dto\OrderResponseDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders', name: 'order_create', methods: ['POST'])]
final class CreateOrderController
{
    public function __invoke(
        CreateOrderRequestDto $createOrderRequestDto,
        OrderServiceIntrerface $orderService,
    ): JsonResponse {
        $order = $orderService->create($createOrderRequestDto);

        return new JsonResponse(
            OrderResponseDto::fromEntity($order)->toArray(),
            Response::HTTP_CREATED,
        );
    }
}
