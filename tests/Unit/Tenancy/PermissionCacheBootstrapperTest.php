<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Models\Tenant;
use App\Tenancy\Bootstrappers\PermissionCacheBootstrapper;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionCacheBootstrapperTest extends TestCase
{
    public function test_aisla_la_clave_de_cache_de_permisos_por_tenant(): void
    {
        config()->set('permission.cache.key', 'spatie.permission.cache');

        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache';
        $registrar->forgetCachedPermissions();

        (new PermissionCacheBootstrapper)->bootstrap(Tenant::factory()->make(['id' => 'acme']));

        $this->assertSame('spatie.permission.cache.tenant.acme', $registrar->cacheKey);
    }

    public function test_restaura_la_clave_de_cache_central_al_revertir(): void
    {
        config()->set('permission.cache.key', 'spatie.permission.cache');

        $registrar = app(PermissionRegistrar::class);
        $bootstrapper = new PermissionCacheBootstrapper;

        $bootstrapper->bootstrap(Tenant::factory()->make(['id' => 'acme']));
        $bootstrapper->revert();

        $this->assertSame('spatie.permission.cache', $registrar->cacheKey);
    }
}
