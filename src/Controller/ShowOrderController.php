<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\OrderResponseDto;
use App\Entity\Order;
use App\Service\Api\OrderServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{id}', name: 'order_show', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
final class ShowOrderController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {
    }

    public function __invoke(Order $order): JsonResponse
    {
        $orderArray = $this->orderService->convertToArray($order);

        return new JsonResponse(
            $orderArray, Response::HTTP_CREATED,
        );
    }
}
