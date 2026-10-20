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

class ShowTest extends TestCase
{
    use RefreshDatabase;
    use WithoutTenantProvisioning;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutTenantProvisioning();
        $this->seed(CentralAccessSeeder::class);
    }

    public function test_super_admin_can_view_tenant_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $tenant = Tenant::factory()->create(['id' => 'acme']);

        $this->actingAs($user)
            ->get(route('admin.tenants.show', $tenant))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('admin/tenants/Show')->has('tenant'));
    }

    public function test_show_returns_404_for_nonexistent_tenant(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $this->actingAs($user)->get(route('admin.tenants.show', 'nonexistent'))->assertStatus(404);
    }

    public function test_user_without_permission_cannot_view_tenant(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($user)->get(route('admin.tenants.show', $tenant))->assertStatus(403);
    }
}
