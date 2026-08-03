<?php

use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSaleTransactionTypes(): void
{
    TransactionType::create(['trans_type_code' => 'FACV', 'trans_type_description' => 'Factura Venta', 'trans_type_factor' => '-1']);
    TransactionType::create(['trans_type_code' => 'DEVV', 'trans_type_description' => 'Devolucion Venta', 'trans_type_factor' => '1']);
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard ventas card shows today sales by currency', function () {
    createSaleTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    $today = now()->format('Y-m-d');

    SaleOrder::factory()->facv()->create(['sls_date' => $today, 'sls_coin' => 'USD', 'sls_total' => 50]);
    SaleOrder::factory()->facv()->create(['sls_date' => $today, 'sls_coin' => 'BS', 'sls_total' => 20]);
    SaleOrder::factory()->facv()->create(['sls_date' => now()->subDay()->format('Y-m-d'), 'sls_coin' => 'USD', 'sls_total' => 999]);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('categoryAggregates.ventas.total.USD', 50)
            ->where('categoryAggregates.ventas.total.BS', 20));
});

test('ventas diarias page groups sales by day and currency', function () {
    createSaleTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    SaleOrder::factory()->facv()->create(['sls_date' => '2026-08-01', 'sls_coin' => 'USD', 'sls_total' => 100.5]);
    SaleOrder::factory()->facv()->create(['sls_date' => '2026-08-01', 'sls_coin' => 'COP', 'sls_total' => 5000]);
    SaleOrder::factory()->devv()->create(['sls_date' => '2026-08-01', 'sls_coin' => 'USD', 'sls_total' => 999]);

    $this->get(route('dashboard.ventas-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/VentasDiarias')
            ->has('daily', 1)
            ->where('daily.0.date', '2026-08-01')
            ->where('daily.0.count', 2)
            ->where('daily.0.usd', 100.5)
            ->where('daily.0.cop', 5000)
            ->where('daily.0.bs', 0)
            ->where('from', '2026-08-01')
            ->where('to', '2026-08-31'));
});

test('ventas diarias page only includes days with sales', function () {
    createSaleTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    SaleOrder::factory()->facv()->create(['sls_date' => '2026-08-02', 'sls_coin' => 'USD', 'sls_total' => 30]);

    $this->get(route('dashboard.ventas-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/VentasDiarias')
            ->has('daily', 1)
            ->where('daily.0.date', '2026-08-02'));
});
