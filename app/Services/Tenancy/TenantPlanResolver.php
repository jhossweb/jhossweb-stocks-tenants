<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Enum\Tenancy\TenantFeature;
use App\Models\Plan;
use App\Models\Tenant;

class TenantPlanResolver
{
    public function currentTenant(): ?Tenant
    {
        if (tenancy()->initialized) {
            $tenant = tenant();

            return $tenant instanceof Tenant ? $tenant : null;
        }

        // Permite resolver un tenant fijado manualmente (tests o jobs).
        if (app()->bound('tenant')) {
            $tenant = app('tenant');

            return $tenant instanceof Tenant ? $tenant : null;
        }

        return null;
    }

    public function currentPlan(): ?Plan
    {
        $tenant = $this->currentTenant();

        if ($tenant === null) {
            return null;
        }

        return $tenant->activeSubscription()?->plan;
    }

    /**
     * Sin tenant o sin plan asignado se permite la funcionalidad para no
     * romper tenants existentes (compatibilidad hacia atrás).
     */
    public function allows(string|TenantFeature $feature): bool
    {
        $plan = $this->currentPlan();

        if ($plan === null) {
            return true;
        }

        return $plan->hasFeature($feature);
    }
}
