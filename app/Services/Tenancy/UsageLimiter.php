<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\TenantUsage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsageLimiter
{
    protected function getCurrentTenant()
    {
        $tenancy = tenancy();

        if (property_exists($tenancy, 'tenant') && $tenancy->tenant) {
            return $tenancy->tenant;
        }

        if (method_exists($tenancy, 'tenant')) {
            try {
                return $tenancy->tenant();
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (method_exists($tenancy, 'getTenant')) {
            try {
                return $tenancy->getTenant();
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (function_exists('tenant') && app()->bound('tenant')) {
            try {
                return app('tenant');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return null;
    }

    public function getLimit(string $key): ?int
    {
        $tenant = $this->getCurrentTenant();
        if (! $tenant) {
            return null;
        }

        $subscription = method_exists($tenant, 'activeSubscription') ? $tenant->activeSubscription() : null;
        $plan = $subscription?->plan;

        if (! $plan) {
            return null;
        }

        $limits = $plan->limits;

        if (! is_array($limits)) {
            return null;
        }

        $value = data_get($limits, $key);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    public function getCurrentPeriod(): string
    {
        return Carbon::now()->format('Y-m');
    }

    public function getUsage(string $key, ?string $period = null): int
    {
        $tenant = $this->getCurrentTenant();
        if (! $tenant) {
            return 0;
        }

        // Total counts for customers/suppliers
        if ($key === 'customers.max_total') {
            try {
                return Customer::count();
            } catch (\Throwable $e) {
                return 0;
            }
        }

        if ($key === 'suppliers.max_total') {
            try {
                return Supplier::count();
            } catch (\Throwable $e) {
                return 0;
            }
        }

        $period = $period ?? $this->getCurrentPeriod();
        $tenantId = method_exists($tenant, 'getTenantKey') ? $tenant->getTenantKey() : ($tenant->id ?? null);

        if (! $tenantId) {
            return 0;
        }

        $usage = TenantUsage::query()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->where('period', $period)
            ->first();

        return $usage?->count ?? 0;
    }

    public function canCreate(string $key, int $amount = 1): bool
    {
        $limit = $this->getLimit($key);

        if ($limit === null) {
            return true;
        }

        $usage = $this->getUsage($key);

        return ($usage + $amount) <= $limit;
    }

    public function assertCanCreate(string $key, int $amount = 1): void
    {
        if ($this->canCreate($key, $amount)) {
            return;
        }

        $limit = $this->getLimit($key);
        $usage = $this->getUsage($key);

        $labels = [
            'sales.invoices_monthly' => 'facturas mensuales',
            'purchases.returns_monthly' => 'devoluciones de compra mensuales',
            'customers.max_total' => 'clientes',
            'suppliers.max_total' => 'proveedores',
        ];

        $label = $labels[$key] ?? 'este recurso';

        throw new \DomainException("Has alcanzado el límite de {$label} para tu plan ({$usage}/{$limit}). Contacta al administrador para actualizar tu plan.");
    }

    public function increment(string $key, int $amount = 1): void
    {
        $tenant = $this->getCurrentTenant();
        if (! $tenant) {
            return;
        }

        // Don't increment for total counts
        if ($key === 'customers.max_total' || $key === 'suppliers.max_total') {
            return;
        }

        $period = $this->getCurrentPeriod();
        $tenantId = method_exists($tenant, 'getTenantKey') ? $tenant->getTenantKey() : ($tenant->id ?? null);

        if (! $tenantId) {
            return;
        }

        try {
            DB::transaction(function () use ($tenantId, $key, $period, $amount) {
                $usage = TenantUsage::query()
                    ->where('tenant_id', $tenantId)
                    ->where('key', $key)
                    ->where('period', $period)
                    ->lockForUpdate()
                    ->first();

                if ($usage) {
                    $usage->count += $amount;
                    $usage->save();
                } else {
                    TenantUsage::create([
                        'tenant_id' => $tenantId,
                        'key' => $key,
                        'period' => $period,
                        'count' => $amount,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Ignore usage tracking errors to not block core operations
        }
    }

    public function remaining(string $key): ?int
    {
        $limit = $this->getLimit($key);

        if ($limit === null) {
            return null;
        }

        $usage = $this->getUsage($key);

        return max(0, $limit - $usage);
    }
}
