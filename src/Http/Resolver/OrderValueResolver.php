<?php

declare(strict_types=1);

namespace App\Http\Resolver;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class OrderValueResolver implements ValueResolverInterface
{
    public function __construct(private readonly OrderRepository $orderRepository)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Order::class) {
            return [];
        }

        $id = $request->attributes->get('id');

        if (!\is_string($id) || !Uuid::isValid($id)) {
            throw new NotFoundHttpException('Order not found.');
        }

        $order = $this->orderRepository->find($id);

        if ($order === null) {
            throw new NotFoundHttpException('Order not found.');
        }

        yield $order;
    }
}
