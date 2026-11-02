<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Enum\Tenancy\TenantLimit;
use App\Exceptions\UsageLimitExceededException;
use App\Models\TenantLimitOverride;

class UsageLimiter
{
    public function __construct(
        private readonly TenantPlanResolver $resolver,
        private readonly PlanUsageCounter $counter,
    ) {}

    /**
     * Resuelve el máximo permitido. Un override del tenant tiene prioridad
     * sobre el valor del plan. `null` significa sin límite.
     */
    public function getLimit(string|TenantLimit $limit): ?int
    {
        $limit = $this->toLimit($limit);

        $tenant = $this->resolver->currentTenant();

        if ($tenant === null) {
            return null;
        }

        $override = TenantLimitOverride::valueFor($tenant->getTenantKey(), $limit);

        if ($override !== null) {
            return $override;
        }

        $plan = $this->resolver->currentPlan();

        if ($plan === null) {
            return null;
        }

        $limits = $plan->limits;

        if (! is_array($limits)) {
            return null;
        }

        $key = $limit->value;
        $value = array_key_exists($key, $limits) ? $limits[$key] : data_get($limits, $key);

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    public function getUsage(string|TenantLimit $limit): int
    {
        if ($this->resolver->currentTenant() === null) {
            return 0;
        }

        return $this->counter->count($this->toLimit($limit));
    }

    public function canCreate(string|TenantLimit $limit, int $amount = 1): bool
    {
        $max = $this->getLimit($limit);

        if ($max === null) {
            return true;
        }

        return ($this->getUsage($limit) + $amount) <= $max;
    }

    public function assertCanCreate(string|TenantLimit $limit, int $amount = 1): void
    {
        $limit = $this->toLimit($limit);

        if ($this->canCreate($limit, $amount)) {
            return;
        }

        throw new UsageLimitExceededException(
            $limit,
            $this->getUsage($limit),
            (int) $this->getLimit($limit),
        );
    }

    public function remaining(string|TenantLimit $limit): ?int
    {
        $max = $this->getLimit($limit);

        if ($max === null) {
            return null;
        }

        return max(0, $max - $this->getUsage($limit));
    }

    public function messageFor(string|TenantLimit $limit): string
    {
        $limit = $this->toLimit($limit);
        $usage = $this->getUsage($limit);
        $max = (int) $this->getLimit($limit);

        return "Has alcanzado el límite de {$limit->label()} para tu plan ({$usage}/{$max}). Contacta al administrador para actualizar tu plan.";
    }

    private function toLimit(string|TenantLimit $limit): TenantLimit
    {
        return $limit instanceof TenantLimit ? $limit : TenantLimit::from($limit);
    }
}
