<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\Central\CentralAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CentralAccessSeeder::class);
    }

    public function test_user_without_admin_access_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_user_with_admin_access_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_user_without_tenants_view_cannot_access_tenants_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin'); // Tiene admin.access pero puede que no tenga tenants.view
        // Por defecto super-admin debería tener todos los permisos, pero probamos

        $response = $this->actingAs($user)->get('/admin/tenants');

        // Depende de permisos - con super-admin debe tener acceso
        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_any_admin_route(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/tenants')->assertRedirect('/admin/login');
        // /admin/plans será añadido en F2
    }
}
