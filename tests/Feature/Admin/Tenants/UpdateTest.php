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

class UpdateTest extends TestCase
{
    use RefreshDatabase;
    use WithoutTenantProvisioning;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutTenantProvisioning();
        $this->seed(CentralAccessSeeder::class);
    }

    public function test_super_admin_can_update_tenant(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $tenant = Tenant::factory()->create(['id' => 'acme', 'data' => ['name' => 'Acme']]);

        $payload = [
            'name' => 'Acme Corp Updated',
            'rif' => 'J-12345678-9',
            'address' => 'Av. Principal, Caracas',
            'phone' => '0212-1234567',
            'admin_email' => 'admin@acme.com',
            'admin_name' => 'Admin Acme',
            'internal_notes' => 'Notas internas',
        ];

        $this->actingAs($user)
            ->put(route('admin.tenants.update', $tenant), $payload)
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHas('success');

        $tenant->refresh();
        $this->assertSame('Acme Corp Updated', $tenant->name);
        $this->assertSame('admin@acme.com', $tenant->admin_email);
    }

    public function test_update_validates_email_format(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $tenant = Tenant::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.tenants.update', $tenant), ['admin_email' => 'invalid-email'])
            ->assertSessionHasErrors('admin_email');
    }

    public function test_user_without_permission_cannot_update_tenant(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.tenants.update', $tenant), ['name' => 'X'])
            ->assertStatus(403);
    }
}
