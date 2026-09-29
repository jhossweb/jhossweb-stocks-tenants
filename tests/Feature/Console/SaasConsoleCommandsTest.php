<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stancl\Tenancy\Events\TenantCreated;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake([TenantCreated::class]);
});

test('el comando tenant:new crea un tenant con sus opciones', function () {
    Event::fake([TenantCreated::class]);

    $this->artisan('tenant:new', [
        '--name' => 'Comando C.A.',
        '--slug' => 'comando-ca',
        '--admin-name' => 'Pepe',
        '--admin-email' => 'pepe@comando.com',
        '--admin-password' => 'secret-password',
        '--plan' => 'premium',
        '--status' => 'suspended',
    ])->assertSuccessful();

    $this->assertDatabaseHas('tenants', [
        'id' => 'comando-ca',
        'name' => 'Comando C.A.',
        'plan' => 'premium',
        'status' => 'suspended',
    ]);
    $this->assertDatabaseHas('domains', [
        'tenant_id' => 'comando-ca',
        'domain' => 'comando-ca.lvh.me',
    ]);

    Event::assertDispatched(TenantCreated::class);
});

test('el comando tenant:new falla con estado inválido', function () {
    $this->artisan('tenant:new', [
        '--name' => 'Comando C.A.',
        '--admin-name' => 'Pepe',
        '--admin-email' => 'pepe@comando.com',
        '--admin-password' => 'secret-password',
        '--status' => 'nope',
    ])->assertFailed();

    $this->assertDatabaseCount('tenants', 0);
});

test('el comando admin:promote marca a un usuario como super admin', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->artisan('admin:promote', ['email' => $user->email])->assertSuccessful();

    $this->assertTrue($user->fresh()->is_admin);
});

test('el comando admin:promote falla si el usuario no existe', function () {
    $this->artisan('admin:promote', ['email' => 'nobody@example.com'])
        ->assertFailed();
});
