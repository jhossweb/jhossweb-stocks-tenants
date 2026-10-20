<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Models\Tenant;
use App\Services\Tenancy\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\WithoutTenantProvisioning;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithoutTenantProvisioning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutTenantProvisioning();
    }

    public function test_service_can_create_tenant_with_minimal_data(): void
    {
        $service = app(TenantService::class);
        $tenant = $service->create([
            'id' => 'acme-test',
            'name' => 'Acme Test',
            'admin_email' => 'admin@acmetest.com',
            'address' => 'Av. Principal, Caracas',
            'phone' => '0212-1234567',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertSame('acme-test', $tenant->getKey());
        $this->assertSame('Acme Test', $tenant->name);
        $this->assertSame('active', $tenant->status);
    }

    public function test_service_sets_trial_ends_at_when_provided(): void
    {
        $service = app(TenantService::class);
        $endsAt = now()->addDays(30)->toDateTimeString();

        $tenant = $service->create([
            'id' => 'trial-co',
            'name' => 'Trial Co',
            'admin_email' => 'admin@trial.co',
            'address' => 'Av. Principal, Caracas',
            'phone' => '0212-1234567',
            'trial_ends_at' => $endsAt,
        ]);

        $this->assertSame($endsAt, $tenant->trial_ends_at ? $tenant->trial_ends_at->toDateTimeString() : null);
    }

    public function test_service_can_update_tenant_data(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Old']);
        $service = app(TenantService::class);

        $updated = $service->update($tenant, ['name' => 'New Name', 'internal_notes' => 'Note']);

        $this->assertSame('New Name', $updated->name);
        $this->assertSame('Note', $updated->internal_notes);
    }

    public function test_service_prevents_invalid_status_transition(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'suspended']);
        $service = app(TenantService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->changeStatus($tenant, 'pending', null, 1);
    }

    public function test_service_allows_valid_status_transition(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'pending']);
        $service = app(TenantService::class);

        $updated = $service->changeStatus($tenant, 'active', null, 1);

        $this->assertSame('active', $updated->status);
    }

    public function test_manual_suspension_marks_manual_source(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $service = app(TenantService::class);

        $updated = $service->suspend($tenant, 'Reason', 1);

        $this->assertSame('suspended', $updated->status);
        $this->assertSame('manual', $updated->status_source);
        $this->assertSame('Reason', $updated->suspended_reason);
        $this->assertNotNull($updated->suspended_at ? $updated->suspended_at->toDateTimeString() : null);
        $this->assertSame(1, $updated->suspended_by);
    }

    public function test_service_can_activate_tenant(): void
    {
        $tenant = Tenant::factory()->create(['data' => ['status' => 'suspended', 'status_source' => 'manual']]);
        $service = app(TenantService::class);

        $updated = $service->activate($tenant);

        $this->assertSame('active', $updated->status);
        $this->assertNull($updated->suspended_reason);
    }

    public function test_service_hashes_admin_password_when_provided(): void
    {
        $service = app(TenantService::class);

        $tenant = $service->create([
            'id' => 'pass-co',
            'name' => 'Pass Co',
            'admin_email' => 'admin@pass.co',
            'admin_password' => 'super-secret',
            'address' => 'Av. Principal, Caracas',
            'phone' => '0212-1234567',
        ]);

        $this->assertNotNull($tenant->admin_password);
        $this->assertNotSame('super-secret', $tenant->admin_password);
        $this->assertTrue(Hash::check('super-secret', (string) $tenant->admin_password));
    }
}
