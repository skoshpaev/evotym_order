<?php

declare(strict_types=1);

namespace App\Http\Resolver;

use App\Dto\CreateOrderPayloadDto;
use App\Dto\CreateOrderRequestDto;
use App\Exception\ApiValidationException;
use App\Repository\ProductRepository;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateOrderRequestDtoResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== CreateOrderRequestDto::class || !$request->isMethod(Request::METHOD_POST)) {
            return [];
        }

        try {
            $payload = $request->toArray();
        } catch (JsonException $exception) {
            throw new BadRequestHttpException('Request body must be valid JSON.', $exception);
        }

        $errors = [];

        $productId = $this->extractString($payload, 'productId', $errors);
        $customerName = $this->extractString($payload, 'customerName', $errors);
        $quantityOrdered = $this->extractInteger($payload, 'quantityOrdered', $errors);

        if ($errors !== []) {
            throw ApiValidationException::fromErrors($errors);
        }

        $payloadDto = new CreateOrderPayloadDto($productId, trim($customerName), $quantityOrdered);
        $violations = $this->validator->validate($payloadDto);

        if (\count($violations) > 0) {
            throw ApiValidationException::fromViolations($violations);
        }

        $product = $this->productRepository->find($payloadDto->productId);

        if ($product === null) {
            throw ApiValidationException::fromErrors([
                'productId' => ['Product not found.'],
            ]);
        }

        if ($payloadDto->quantityOrdered > $product->getQuantity()) {
            throw ApiValidationException::fromErrors([
                'quantityOrdered' => ['Ordered quantity exceeds available stock.'],
            ]);
        }

        yield new CreateOrderRequestDto($product, $payloadDto->customerName, $payloadDto->quantityOrdered);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $errors
     */
    private function extractString(array $payload, string $field, array &$errors): string
    {
        if (!array_key_exists($field, $payload)) {
            $errors[$field][] = 'This field is required.';

            return '';
        }

        if (!\is_string($payload[$field])) {
            $errors[$field][] = 'This field must be a string.';

            return '';
        }

        return $payload[$field];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $errors
     */
    private function extractInteger(array $payload, string $field, array &$errors): int
    {
        if (!array_key_exists($field, $payload)) {
            $errors[$field][] = 'This field is required.';

            return 0;
        }

        if (!\is_int($payload[$field])) {
            $errors[$field][] = 'This field must be an integer.';

            return 0;
        }

        return $payload[$field];
    }
}
