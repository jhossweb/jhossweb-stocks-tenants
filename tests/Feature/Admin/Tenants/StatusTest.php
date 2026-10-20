<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Tenants;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\Central\CentralAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithoutTenantProvisioning;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;
    use WithoutTenantProvisioning;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutTenantProvisioning();
        $this->seed(CentralAccessSeeder::class);
    }

    public function test_super_admin_can_activate_tenant(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $tenant = Tenant::factory()->create(['status' => 'pending']);

        $this->actingAs($user)
            ->patch(route('admin.tenants.update-status', $tenant), ['status' => 'active'])
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHas('success');

        $tenant->refresh();
        $this->assertSame('active', $tenant->status);
    }

    public function test_super_admin_can_suspend_tenant_with_reason(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $tenant = Tenant::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->patch(route('admin.tenants.update-status', $tenant), [
                'status' => 'suspended',
                'suspended_reason' => 'Violación de Términos',
            ])
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHas('success');

        $tenant->refresh();
        $this->assertSame('suspended', $tenant->status);
        $this->assertSame('Violación de Términos', $tenant->suspended_reason);
    }

    public function test_cannot_suspend_without_reason(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $tenant = Tenant::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->patch(route('admin.tenants.update-status', $tenant), ['status' => 'suspended'])
            ->assertSessionHasErrors('suspended_reason');
    }

    public function test_invalid_status_transition_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $tenant = Tenant::factory()->create(['status' => 'suspended']);

        $this->actingAs($user)
            ->patch(route('admin.tenants.update-status', $tenant), ['status' => 'pending'])
            ->assertSessionHasErrors('status');
    }

    public function test_manual_suspension_sets_status_source_manual(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $tenant = Tenant::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->patch(route('admin.tenants.update-status', $tenant), [
                'status' => 'suspended',
                'suspended_reason' => 'Manual',
            ]);

        $tenant->refresh();
        $this->assertSame('manual', $tenant->status_source);
        $this->assertNotNull($tenant->suspended_at);
        $this->assertNotNull($tenant->suspended_by);
    }
}
