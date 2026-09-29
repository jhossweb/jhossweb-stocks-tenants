<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class RegisterTenantData
{
    public function __construct(
        public string $companyName,
        public string $adminName,
        public string $adminEmail,
        public string $adminPassword,
        public ?string $slug = null,
        public ?string $plan = null,
        public ?string $status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            companyName: (string) $data['company_name'],
            adminName: (string) $data['admin_name'],
            adminEmail: (string) $data['admin_email'],
            adminPassword: (string) $data['admin_password'],
        );
    }
}
