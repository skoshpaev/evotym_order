<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateOrderRequestDto;
use App\Dto\OrderResponseDto;
use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders')]
final class OrderController
{
    #[Route('', name: 'order_create', methods: ['POST'])]
    public function create(
        CreateOrderRequestDto $createOrderRequestDto,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $product = $createOrderRequestDto->product;
        $product->decreaseQuantity($createOrderRequestDto->quantityOrdered);

        $order = Order::create(
            $product,
            $createOrderRequestDto->customerName,
            $createOrderRequestDto->quantityOrdered,
        );

        $entityManager->persist($order);
        $entityManager->flush();

        return new JsonResponse(
            OrderResponseDto::fromEntity($order)->toArray(),
            Response::HTTP_CREATED,
        );
    }

    #[Route('', name: 'order_list', methods: ['GET'])]
    public function list(OrderRepository $orderRepository): JsonResponse
    {
        $data = array_map(
            static fn (Order $order): array => OrderResponseDto::fromEntity($order)->toArray(),
            $orderRepository->findAllOrderedByIdDesc(),
        );

        return new JsonResponse(['data' => $data]);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function show(Order $order): JsonResponse
    {
        return new JsonResponse(OrderResponseDto::fromEntity($order)->toArray());
    }
}
