<?php

declare(strict_types=1);

namespace App\Tenancy\Bootstrappers;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

final class PermissionCacheBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = $this->tenantCacheKey($tenant);
        $registrar->forgetCachedPermissions();
    }

    public function revert(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = (string) config('permission.cache.key');
        $registrar->forgetCachedPermissions();
    }

    private function tenantCacheKey(Tenant $tenant): string
    {
        return config('permission.cache.key').'.tenant.'.$tenant->getTenantKey();
    }
}
