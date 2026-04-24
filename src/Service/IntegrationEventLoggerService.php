<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\IntegrationEventLoggerServiceInterface;
use JsonException;

final class IntegrationEventLoggerService implements IntegrationEventLoggerServiceInterface
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private function log(string $level, string $message, array $context): void
    {
        try {
            error_log((string) json_encode([
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ], JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            error_log(sprintf('[%s] %s', strtoupper($level), $message));
        }
    }
}
