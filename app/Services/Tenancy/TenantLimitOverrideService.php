<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Enum\Tenancy\TenantLimit;
use App\Models\Tenant;
use App\Models\TenantLimitOverride;

final class TenantLimitOverrideService
{
    /**
     * Sincroniza los overrides del tenant. Un valor null/vacío elimina el
     * override y deja que aplique el límite del plan.
     *
     * @param  array<string, int|string|null>  $values
     */
    public function sync(Tenant $tenant, array $values): void
    {
        $known = array_flip(TenantLimit::keys());

        TenantLimitOverride::query()->getConnection()->transaction(function () use ($tenant, $values, $known): void {
            foreach ($values as $key => $value) {
                if (! array_key_exists($key, $known)) {
                    continue;
                }

                if ($value === null || $value === '') {
                    TenantLimitOverride::query()
                        ->where('tenant_id', $tenant->getTenantKey())
                        ->where('key', $key)
                        ->delete();

                    continue;
                }

                TenantLimitOverride::query()->updateOrCreate(
                    ['tenant_id' => $tenant->getTenantKey(), 'key' => $key],
                    ['value' => max(0, (int) $value)],
                );
            }
        });
    }
}
