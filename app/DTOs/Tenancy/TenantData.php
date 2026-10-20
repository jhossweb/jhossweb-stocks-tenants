<?php

declare(strict_types=1);

namespace App\DTOs\Tenancy;

readonly class TenantData
{
    public function __construct(
        public ?string $id = null,
        public string $name = '',
        public string $rif = '',
        public string $address = '',
        public string $phone = '',
        public string $admin_name = '',
        public string $admin_email = '',
        public ?string $plan = null,
        public ?string $status = null,
        public ?string $internal_notes = null,
        public ?\DateTimeInterface $trial_ends_at = null,
    ) {}
}
