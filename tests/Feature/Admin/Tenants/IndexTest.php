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

class IndexTest extends TestCase
{
    use RefreshDatabase;
    use WithoutTenantProvisioning;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutTenantProvisioning();
        $this->seed(CentralAccessSeeder::class);
    }

    public function test_guest_cannot_access_tenants_index(): void
    {
        $this->get(route('admin.tenants.index'))->assertRedirect('/admin/login');
    }

    public function test_user_without_admin_access_cannot_access_tenants_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.tenants.index'))->assertStatus(403);
    }

    public function test_super_admin_can_view_tenants_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        Tenant::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.tenants.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('admin/tenants/Index')->has('tenants.data'));
    }

    public function test_tenants_index_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        Tenant::factory()->create(['status' => 'active']);
        Tenant::factory()->create(['status' => 'pending']);

        $this->actingAs($user)
            ->get(route('admin.tenants.index', ['status' => 'pending']))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->where('filters.status', 'pending'));
    }

    public function test_tenants_index_supports_search(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        Tenant::factory()->create(['id' => 'acme', 'data' => ['name' => 'Acme Corp']]);
        Tenant::factory()->create(['id' => 'beta', 'data' => ['name' => 'Beta LLC']]);

        $this->actingAs($user)
            ->get(route('admin.tenants.index', ['search' => 'acme']))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->has('tenants.data'));
    }

    public function test_tenants_index_is_paginated(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        Tenant::factory()->count(15)->create();

        $this->actingAs($user)
            ->get(route('admin.tenants.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->has('tenants.links'));
    }
}
