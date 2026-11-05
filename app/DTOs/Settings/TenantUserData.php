<?php

declare(strict_types=1);

namespace App\DTOs\Settings;

readonly class TenantUserData
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public array $roles = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: isset($data['password']) && $data['password'] !== '' ? (string) $data['password'] : null,
            roles: array_values((array) ($data['roles'] ?? [])),
        );
    }
}
