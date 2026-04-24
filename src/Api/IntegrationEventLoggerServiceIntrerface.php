<?php

declare(strict_types=1);

namespace App\Api;

interface IntegrationEventLoggerServiceIntrerface
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * @param array<string, scalar|null> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * @param array<string, scalar|null> $context
     */
    public function error(string $message, array $context = []): void;
}
