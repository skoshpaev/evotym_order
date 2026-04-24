<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\OrderResponseDto;
use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders', name: 'order_list', methods: ['GET'])]
final class ListOrderController
{
    public function __invoke(OrderRepository $orderRepository): JsonResponse
    {
        $data = array_map(
            static fn (Order $order): array => OrderResponseDto::fromEntity($order)->toArray(),
            $orderRepository->findAllOrderedByIdDesc(),
        );

        return new JsonResponse(['data' => $data]);
    }
}
