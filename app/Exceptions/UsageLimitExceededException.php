<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enum\Tenancy\TenantLimit;
use RuntimeException;

class UsageLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly TenantLimit $limit,
        public readonly int $usage,
        public readonly int $max,
    ) {
        parent::__construct(
            "Has alcanzado el límite de {$limit->label()} para tu plan ({$usage}/{$max}). Contacta al administrador para actualizar tu plan."
        );
    }
}
