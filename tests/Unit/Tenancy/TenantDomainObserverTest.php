<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithoutTenantProvisioning;
use Tests\TestCase;

class TenantDomainObserverTest extends TestCase
{
    use RefreshDatabase;
    use WithoutTenantProvisioning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutTenantProvisioning();
    }

    public function test_creacion_de_tenant_crea_su_subdominio_automaticamente(): void
    {
        $tenant = Tenant::factory()->create(['id' => 'vidrio-sur']);

        $this->assertDatabaseHas('domains', [
            'tenant_id' => 'vidrio-sur',
            'domain' => 'vidrio-sur.lvh.me',
        ]);

        $this->assertSame('vidrio-sur.lvh.me', $tenant->domain());
    }

    public function test_el_subdominio_se_deriva_siempre_del_id_del_tenant(): void
    {
        Tenant::factory()->create(['id' => 'acero-norte', 'status' => 'pending']);

        $this->assertDatabaseHas('domains', [
            'tenant_id' => 'acero-norte',
            'domain' => 'acero-norte.lvh.me',
        ]);
    }
}
