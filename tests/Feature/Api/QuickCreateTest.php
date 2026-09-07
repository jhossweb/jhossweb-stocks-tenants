<?php

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('crea cuenta bancaria vía AJAX y devuelve JSON 201', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/bank-accounts', [
            'bank_name' => 'Banco Prueba',
            'bank_account_number' => '0123456789',
            'bank_type' => 'CORRIENTE',
            'bank_currency' => 'BS',
            'bank_status' => true,
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['bank_name' => 'Banco Prueba']);

    expect(BankAccount::where('bank_name', 'Banco Prueba')->exists())->toBeTrue();
});

test('crea cliente vía AJAX con solo los campos obligatorios', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/customers', [
            'clt_name' => 'Cliente Rápido',
            'clt_address' => 'Dirección 123',
            'clt_phone' => '04141234567',
            'clt_email' => 'rapido@test.com',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['clt_name' => 'Cliente Rápido']);

    expect(Customer::where('clt_name', 'Cliente Rápido')->exists())->toBeTrue();
});

test('crea proveedor vía AJAX con solo los campos obligatorios', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/suppliers', [
            'supplier_name' => 'Proveedor Rápido',
            'supplier_address' => 'Dirección 456',
            'supplier_phone' => '04147654321',
            'supplier_email' => 'proveedor@test.com',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['supplier_name' => 'Proveedor Rápido']);

    expect(Supplier::where('supplier_name', 'Proveedor Rápido')->exists())->toBeTrue();
});

test('validación fallida devuelve 422 con errores por campo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/bank-accounts', ['bank_type' => 'INVALIDO'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bank_name', 'bank_account_number']);

    $this->actingAs($user)
        ->postJson('/api/customers', ['clt_name' => 'X'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['clt_address', 'clt_phone', 'clt_email']);

    $this->actingAs($user)
        ->postJson('/api/suppliers', ['supplier_name' => 'X'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['supplier_address', 'supplier_phone', 'supplier_email']);
});

test('rechaza duplicados únicos (teléfono, email, nombre de banco)', function () {
    Customer::factory()->create(['clt_phone' => '04141111111', 'clt_email' => 'dup@test.com']);
    BankAccount::factory()->create(['bank_name' => 'Banco Duplicado']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/customers', [
            'clt_name' => 'Otro',
            'clt_address' => 'Dir',
            'clt_phone' => '04141111111',
            'clt_email' => 'nuevo@test.com',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors(['clt_phone']);

    $this->actingAs($user)
        ->postJson('/api/bank-accounts', [
            'bank_name' => 'Banco Duplicado',
            'bank_account_number' => '999999',
            'bank_type' => 'AHORRO',
            'bank_currency' => 'USD',
            'bank_status' => true,
        ])->assertUnprocessable()
        ->assertJsonValidationErrors(['bank_name']);
});

test('requiere autenticación', function () {
    $this->postJson('/api/customers', [])->assertUnauthorized();
});
