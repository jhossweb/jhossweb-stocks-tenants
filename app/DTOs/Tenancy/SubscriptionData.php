<?php

declare(strict_types=1);

namespace App\DTOs\Tenancy;

use Carbon\Carbon;
use Illuminate\Support\Arr;

readonly class SubscriptionData
{
    public function __construct(
        public ?string $tenant_id,
        public int $plan_id,
        public ?string $status = null,
        public ?\DateTimeInterface $started_at = null,
        public ?\DateTimeInterface $trial_ends_at = null,
        public ?\DateTimeInterface $current_period_start = null,
        public ?\DateTimeInterface $current_period_end = null,
        public ?\DateTimeInterface $canceled_at = null,
        public int $grace_days = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: Arr::has($data, 'tenant_id') ? (string) $data['tenant_id'] : null,
            plan_id: (int) $data['plan_id'],
            status: Arr::get($data, 'status'),
            started_at: self::date($data, 'started_at'),
            trial_ends_at: self::date($data, 'trial_ends_at'),
            current_period_start: self::date($data, 'current_period_start'),
            current_period_end: self::date($data, 'current_period_end'),
            canceled_at: self::date($data, 'canceled_at'),
            grace_days: (int) ($data['grace_days'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function date(array $data, string $key): ?\DateTimeInterface
    {
        $value = Arr::get($data, $key);

        return $value === null || $value === '' ? null : Carbon::parse((string) $value);
    }
}
