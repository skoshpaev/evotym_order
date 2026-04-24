<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateOrderRequestDto;
use App\Service\Api\OrderServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders', name: 'order_create', methods: ['POST'])]
final class CreateOrderController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {
    }

    public function __invoke(
        CreateOrderRequestDto $createOrderRequestDto,
    ): JsonResponse {
        $order = $this->orderService->create($createOrderRequestDto);
        $orderArray = $this->orderService->convertToArray($order);

        return new JsonResponse(
            $orderArray, Response::HTTP_CREATED,
        );
    }
}
