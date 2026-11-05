<?php

declare(strict_types=1);

namespace App\DTOs\Settings;

readonly class TenantRoleData
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            permissions: array_values((array) ($data['permissions'] ?? [])),
        );
    }
}
