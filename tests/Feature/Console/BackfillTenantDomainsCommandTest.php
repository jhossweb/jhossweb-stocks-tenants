<?php

declare(strict_types=1);

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithoutTenantProvisioning;

uses(RefreshDatabase::class, WithoutTenantProvisioning::class);

beforeEach(function () {
    $this->withoutTenantProvisioning();
});

test('backfill crea el subdominio faltante de tenants legacy', function () {
    $tenant = Tenant::factory()->create(['id' => 'legacy-co']);

    // Simula un tenant de antes de la creación automática de subdominios.
    $tenant->domains()->delete();

    $this->assertDatabaseCount('domains', 0);

    $this->artisan('tenancy:backfill-domains')->assertSuccessful();

    $this->assertDatabaseHas('domains', [
        'tenant_id' => 'legacy-co',
        'domain' => 'legacy-co.lvh.me',
    ]);
});

test('backfill es idempotente', function () {
    $tenant = Tenant::factory()->create(['id' => 'legacy-co']);
    $tenant->domains()->delete();

    $this->artisan('tenancy:backfill-domains')->assertSuccessful();
    $this->artisan('tenancy:backfill-domains')->assertSuccessful();

    $this->assertDatabaseCount('domains', 1);
});
