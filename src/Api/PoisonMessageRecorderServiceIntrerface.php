<?php

declare(strict_types=1);

namespace App\Api;

interface PoisonMessageRecorderServiceIntrerface
{
    public function record(object $message, \Throwable $throwable, string $failureTransportName): void;
}
