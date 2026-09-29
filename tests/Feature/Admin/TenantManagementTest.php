<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);
});

test('un invitado es redirigido al login en el panel de tenants', function () {
    $this->get(route('admin.tenants.index'))->assertRedirect(route('login'));
});

test('un usuario central sin rol admin recibe 403 en el panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.tenants.index'))
        ->assertForbidden();
});

test('el super admin central ve el listado de tenants', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Tenant::create(['id' => 'acme', 'name' => 'Acme C.A.', 'status' => 'active', 'plan' => 'standard']);

    $this->actingAs($admin)
        ->get(route('admin.tenants.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tenants/index')
            ->has('tenants', 1)
            ->where('tenants.0.id', 'acme')
            ->where('tenants.0.plan', 'standard')
            ->where('tenants.0.status', 'active')
        );
});

test('el admin central crea un tenant con datos propios', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.tenants.store'), [
            'company_name' => 'TechGlass C.A.',
            'slug' => 'techglass-ca',
            'plan' => 'premium',
            'status' => 'suspended',
            'admin_name' => 'Ana Gómez',
            'admin_email' => 'ana@techglass.com',
            'admin_password' => 'secret-password',
            'admin_password_confirmation' => 'secret-password',
        ])
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tenants', [
        'id' => 'techglass-ca',
        'name' => 'TechGlass C.A.',
        'plan' => 'premium',
        'status' => 'suspended',
    ]);
    $this->assertDatabaseHas('domains', [
        'tenant_id' => 'techglass-ca',
        'domain' => 'techglass-ca.lvh.me',
    ]);

    Event::assertDispatched(TenantCreated::class);
});

test('el admin central crea un tenant generando el slug del nombre', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.tenants.store'), [
            'company_name' => 'Nueva Empresa Testing',
            'admin_name' => 'Ana Gómez',
            'admin_email' => 'ana@testing.com',
            'admin_password' => 'secret-password',
            'admin_password_confirmation' => 'secret-password',
        ])
        ->assertRedirect(route('admin.tenants.index'));

    $this->assertDatabaseHas('tenants', ['id' => 'nueva-empresa-testing']);

    Event::assertDispatched(TenantCreated::class);
});

test('el admin central no crea un tenant con slug inválido', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.tenants.store'), [
            'company_name' => 'Inválido',
            'slug' => 'slug con espacios',
            'admin_name' => 'Ana Gómez',
            'admin_email' => 'ana@testing.com',
            'admin_password' => 'secret-password',
            'admin_password_confirmation' => 'secret-password',
        ])
        ->assertSessionHasErrors('slug');

    $this->assertDatabaseMissing('tenants', ['name' => 'Inválido']);
});

test('el admin central actualiza nombre, plan y estado de un tenant', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Tenant::create(['id' => 'acme', 'name' => 'Acme C.A.', 'status' => 'active', 'plan' => 'standard']);

    $this->actingAs($admin)
        ->put(route('admin.tenants.update', 'acme'), [
            'name' => 'Acme SRL',
            'plan' => 'premium',
            'status' => 'suspended',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tenants', [
        'id' => 'acme',
        'name' => 'Acme SRL',
        'plan' => 'premium',
        'status' => 'suspended',
    ]);
});

test('el admin central elimina un tenant', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Tenant::create(['id' => 'acme', 'name' => 'Acme C.A.', 'status' => 'active', 'plan' => 'standard']);

    $this->actingAs($admin)
        ->delete(route('admin.tenants.destroy', 'acme'))
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('tenants', ['id' => 'acme']);
});

test('el login de un super admin central redirige al panel de tenants', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.tenants.index'));
});

test('el login de un usuario sin rol admin redirige al dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
});
