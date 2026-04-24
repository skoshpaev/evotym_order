<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\Api\OrderServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders', name: 'order_list', methods: ['GET'])]
final class ListOrderController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $data = [];
        foreach ($this->orderRepository->findAllOrderedByIdDesc() as $order) {
            $data[] = $this->orderService->convertToArray($order);
        }

        return new JsonResponse(['data' => $data]);
    }
}
