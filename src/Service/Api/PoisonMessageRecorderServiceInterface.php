<?php

declare(strict_types=1);

namespace App\Service\Api;

use Throwable;

interface PoisonMessageRecorderServiceInterface
{
    public const POISON_STATUS_POISONED = 'poisoned';

    public function record(object $message, Throwable $throwable, string $failureTransportName): void;
}
