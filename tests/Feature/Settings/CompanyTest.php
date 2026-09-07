<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('página de datos de la empresa renderiza con el registro existente', function () {
    $company = Company::create([
        'cmp_name' => 'Vidrialum C.A.',
        'cmp_rif' => 'J-12345678-9',
        'cmp_address' => 'Av. Principal, Caracas',
        'cmp_phone' => '+58 212 555 1234',
        'cmp_email' => 'contacto@vidrialum.com',
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('company.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Company')
            ->where('company.cmp_name', $company->cmp_name)
            ->where('company.cmp_rif', $company->cmp_rif));
});

test('usuario autenticado puede actualizar los datos de la empresa', function () {
    Company::create([
        'cmp_name' => 'Vidrialum C.A.',
        'cmp_rif' => 'J-12345678-9',
        'cmp_address' => 'Av. Principal, Caracas',
        'cmp_phone' => '+58 212 555 1234',
        'cmp_email' => 'contacto@vidrialum.com',
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->patch(route('company.update'), [
            'cmp_name' => 'Vidrialum S.A.',
            'cmp_rif' => 'J-87654321-0',
            'cmp_address' => 'Nueva Dirección, Valencia',
            'cmp_phone' => '+58 241 555 9999',
            'cmp_email' => 'admin@vidrialum.com',
            'cmp_website' => 'https://vidrialum.com',
        ]);

    $response->assertRedirect(route('company.edit'))
        ->assertSessionHas('success');

    expect(Company::count())->toBe(1);

    $company = Company::first();
    expect($company->cmp_name)->toBe('Vidrialum S.A.')
        ->and($company->cmp_rif)->toBe('J-87654321-0')
        ->and($company->cmp_website)->toBe('https://vidrialum.com');
});

test('crea el registro si la tabla está vacía al guardar', function () {
    expect(Company::count())->toBe(0);

    $response = $this->actingAs(User::factory()->create())
        ->patch(route('company.update'), [
            'cmp_name' => 'Vidrialum C.A.',
            'cmp_rif' => 'V-11222333-4',
            'cmp_address' => 'Dirección de prueba',
            'cmp_phone' => '0212 000 0000',
            'cmp_email' => 'info@vidrialum.com',
        ]);

    $response->assertRedirect();
    expect(Company::count())->toBe(1);
});

test('falla cuando faltan campos requeridos', function (array $overrides) {
    $data = array_merge([
        'cmp_name' => 'Vidrialum C.A.',
        'cmp_rif' => 'J-12345678-9',
        'cmp_address' => 'Dirección',
        'cmp_phone' => '0212 000 0000',
        'cmp_email' => 'info@vidrialum.com',
    ], $overrides);

    foreach ($overrides as $key => $value) {
        if (str_starts_with((string) $value, '__remove__')) {
            unset($data[$key]);
        }
    }

    $this->actingAs(User::factory()->create())
        ->patch(route('company.update'), $data)
        ->assertSessionHasErrors(array_keys($overrides));
})->with([
    'sin nombre' => [['cmp_name' => '__remove__']],
    'sin rif' => [['cmp_rif' => '__remove__']],
    'sin dirección' => [['cmp_address' => '__remove__']],
    'sin teléfono' => [['cmp_phone' => '__remove__']],
    'sin email' => [['cmp_email' => '__remove__']],
]);

test('valida formato del rif y del email y url del website', function () {
    $base = [
        'cmp_name' => 'Vidrialum C.A.',
        'cmp_address' => 'Dirección',
        'cmp_phone' => '0212 000 0000',
    ];

    $this->actingAs(User::factory()->create());

    $this->patch(route('company.update'), array_merge($base, [
        'cmp_rif' => 'INVALIDO',
        'cmp_email' => 'info@vidrialum.com',
    ]))->assertSessionHasErrors(['cmp_rif']);

    $this->patch(route('company.update'), array_merge($base, [
        'cmp_rif' => 'J-12345678-9',
        'cmp_email' => 'no-es-un-email',
    ]))->assertSessionHasErrors(['cmp_email']);

    $this->patch(route('company.update'), array_merge($base, [
        'cmp_rif' => 'J-12345678-9',
        'cmp_email' => 'info@vidrialum.com',
        'cmp_website' => 'no-es-url',
    ]))->assertSessionHasErrors(['cmp_website']);
});

test('website y logo aceptan valores nulos', function () {
    Company::create([
        'cmp_name' => 'Vidrialum C.A.',
        'cmp_rif' => 'J-12345678-9',
        'cmp_address' => 'Dirección',
        'cmp_phone' => '0212 000 0000',
        'cmp_email' => 'info@vidrialum.com',
    ]);

    $this->actingAs(User::factory()->create())
        ->from(route('company.edit'))
        ->followingRedirects()
        ->patch(route('company.update'), [
            'cmp_name' => 'Vidrialum C.A.',
            'cmp_rif' => 'J-12345678-9',
            'cmp_address' => 'Otra dirección',
            'cmp_phone' => '0212 111 1111',
            'cmp_email' => 'info@vidrialum.com',
            'cmp_website' => '',
            'cmp_logo' => null,
        ])->assertOk();

    $company = Company::first();
    expect($company->cmp_website)->toBeNull()
        ->and($company->cmp_logo)->toBeNull();
});
