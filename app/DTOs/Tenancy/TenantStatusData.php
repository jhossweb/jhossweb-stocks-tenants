<?php

declare(strict_types=1);

namespace App\DTOs\Tenancy;

readonly class TenantStatusData
{
    public function __construct(
        public string $status,
        public ?string $suspended_reason = null,
        public ?int $userId = null,
    ) {}
}
