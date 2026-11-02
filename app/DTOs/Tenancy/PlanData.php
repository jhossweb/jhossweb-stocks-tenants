<?php

declare(strict_types=1);

namespace App\DTOs\Tenancy;

readonly class PlanData
{
    /**
     * @param  array<string, mixed>|null  $limits
     * @param  list<string>|null  $features
     * @param  array<int, string>  $module_keys
     */
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public string $billing_cycle,
        public float $price_usd,
        public int $trial_days,
        public bool $is_active,
        public ?array $limits = null,
        public array $module_keys = [],
        public ?array $features = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) $data['code'],
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            billing_cycle: (string) $data['billing_cycle'],
            price_usd: (float) $data['price_usd'],
            trial_days: (int) ($data['trial_days'] ?? 0),
            is_active: (bool) ($data['is_active'] ?? true),
            limits: $data['limits'] ?? null,
            module_keys: array_values((array) ($data['module_keys'] ?? [])),
            features: isset($data['features']) ? array_values((array) $data['features']) : null,
        );
    }
}
