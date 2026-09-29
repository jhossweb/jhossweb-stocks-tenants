<?php

declare(strict_types=1);

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stancl\Tenancy\Events\TenantCreated;

uses(RefreshDatabase::class);

beforeEach(function () {
    // El pipeline real (CreateDatabase/MigrateDatabase) crea schemas pgsql y no
    // aplica en el SQLite :memory: de testing, así que fingimos el evento y
    // solo se persisten las filas de tenants/domains en la central.
    Event::fake([TenantCreated::class]);
});

test('la pantalla de alta de empresa se renderiza', function () {
    $this->get(route('register-company'))
        ->assertOk();
});

test('registra un tenant con slug y dominio derivados del nombre', function () {
    $response = $this->post(route('register-company.store'), [
        'company_name' => 'Vidrialum C.A.',
        'admin_name' => 'Carlos Pérez',
        'admin_email' => 'carlos@vidrialum.com',
        'admin_password' => 'secret-password',
        'admin_password_confirmation' => 'secret-password',
    ]);

    $response->assertRedirect('http://vidrialum-ca.lvh.me:8000/login');

    $this->assertDatabaseHas('tenants', [
        'id' => 'vidrialum-ca',
        'name' => 'Vidrialum C.A.',
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('domains', [
        'domain' => 'vidrialum-ca.lvh.me',
        'tenant_id' => 'vidrialum-ca',
    ]);

    // Los datos de provisioning se serializan en `data` (VirtualColumn) y deben
    // sobrevivir al guardado para que el pipeline cree admin + company.
    $tenant = Tenant::find('vidrialum-ca');
    expect($tenant)
        ->getAttribute('admin_email')->toBe('carlos@vidrialum.com')
        ->getAttribute('admin_name')->toBe('Carlos Pérez')
        ->getAttribute('admin_password')->not->toBe('secret-password');

    Event::assertDispatched(TenantCreated::class);
});

test('no registra con payload inválido', function () {
    $this->post(route('register-company.store'), [
        'company_name' => '',
        'admin_name' => '',
        'admin_email' => 'no-es-email',
        'admin_password' => 'short',
        'admin_password_confirmation' => 'diferente',
    ])->assertSessionHasErrors(['company_name', 'admin_name', 'admin_email', 'admin_password']);

    $this->assertDatabaseCount('tenants', 0);
});

test('genera sufijo numerico cuando el slug ya existe', function () {
    Tenant::create([
        'id' => 'vidrialum',
        'name' => 'Vidrialum',
        'status' => 'active',
    ]);

    $this->post(route('register-company.store'), [
        'company_name' => 'Vidrialum',
        'admin_name' => 'Ana',
        'admin_email' => 'ana@example.com',
        'admin_password' => 'secret-password',
        'admin_password_confirmation' => 'secret-password',
    ])->assertRedirect('http://vidrialum-2.lvh.me:8000/login');

    $this->assertDatabaseHas('tenants', ['id' => 'vidrialum-2']);
});

test('responde 409 con X-Inertia-Location ante peticion Inertia', function () {
    $this->post(route('register-company.store'), [
        'company_name' => 'Repuestos App',
        'admin_name' => 'Ana',
        'admin_email' => 'repuestos@example.com',
        'admin_password' => 'secret-password',
        'admin_password_confirmation' => 'secret-password',
    ], ['X-Inertia' => 'true'])->assertStatus(409)->assertHeader('X-Inertia-Location', 'http://repuestos-app.lvh.me:8000/login');

    $this->assertDatabaseHas('tenants', ['id' => 'repuestos-app']);
});

test('rechaza un admin_email ya registrado en otro tenant', function () {
    $this->post(route('register-company.store'), [
        'company_name' => 'Primera Empresa',
        'admin_name' => 'Ana',
        'admin_email' => 'repetido@example.com',
        'admin_password' => 'secret-password',
        'admin_password_confirmation' => 'secret-password',
    ]);

    $this->post(route('register-company.store'), [
        'company_name' => 'Segunda Empresa',
        'admin_name' => 'Luis',
        'admin_email' => 'repetido@example.com',
        'admin_password' => 'secret-password',
        'admin_password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors(['admin_email']);

    $this->assertDatabaseCount('tenants', 1);
});
