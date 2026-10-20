<?php

declare(strict_types=1);

namespace App\Observers\Tenancy;

use App\Models\Tenant;

class TenantObserver
{
    /**
     * Crea el subdominio del tenant en cuanto se persiste la fila central.
     *
     * El hook `created` corre sobre el evento `eloquent.created: App\Models\Tenant`,
     * que se dispara ANTES del evento `TenantCreated` (pipeline de provisioning).
     * Así el subdominio queda creado de forma determinista e independiente del
     * resultado del aprovisionamiento (CreateDatabase/MigrateDatabase/Seed).
     */
    public function created(Tenant $tenant): void
    {
        $suffix = (string) config('tenancy.tenant_domain_suffix');
        $domain = $tenant->id.'.'.$suffix;

        if ($tenant->domains()->where('domain', $domain)->doesntExist()) {
            $tenant->createDomain($domain);
        }
    }
}
