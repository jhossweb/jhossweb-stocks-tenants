<?php

use App\Models\Rate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Rate::factory()->create([
        'rate_date' => now()->toDateString(),
        'rate_usd' => 795.0,
        'rate_cop' => 3200.0,
        'rate_eur' => 895.0,
        'rate_cop_bs' => 3.5,
    ]);
});

it('proporciona rate_cop_bs en el prop rates de :label', function (string $routeName) {
    $response = $this->get(route($routeName))->assertOk();

    $rates = $response->original->getData()['page']['props']['rates'];

    expect($rates)
        ->not->toBeEmpty()
        ->each->toHaveKey('rate_cop_bs');
})->with([
    'nueva venta' => ['sales.create'],
    'nueva compra' => ['purchases.create'],
    'devolución de compra' => ['purchase-returns.create'],
    'cotización' => ['quotes.create'],
    'nuevo gasto' => ['expenses.create'],
    'devolución de venta' => ['sale-returns.create'],
]);
