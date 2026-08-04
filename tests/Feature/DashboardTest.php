<?php

use App\Models\Customer;
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

test('dashboard ventas card falls back to latest day with sales when today has none', function () {
    createSaleTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    SaleOrder::factory()->facv()->create(['sls_date' => now()->subDay()->format('Y-m-d'), 'sls_coin' => 'USD', 'sls_total' => 75]);
    SaleOrder::factory()->facv()->create(['sls_date' => now()->subDay()->format('Y-m-d'), 'sls_coin' => 'COP', 'sls_total' => 1000]);
    SaleOrder::factory()->facv()->create(['sls_date' => now()->subDays(2)->format('Y-m-d'), 'sls_coin' => 'USD', 'sls_total' => 999]);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('categoryAggregates.ventas.total.USD', 75)
            ->where('categoryAggregates.ventas.total.COP', 1000));
});

test('ventas diarias page lists sales detail paginated', function () {
    createSaleTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['clt_name' => 'Cliente A']);
    SaleOrder::factory()->facv()->create([
        'sls_date' => '2026-08-01',
        'sls_coin' => 'USD',
        'sls_total' => 100.5,
        'sls_customer_id' => $customer->id,
    ]);
    SaleOrder::factory()->facv()->create(['sls_date' => '2026-08-03', 'sls_coin' => 'COP', 'sls_total' => 5000]);
    SaleOrder::factory()->devv()->create(['sls_date' => '2026-08-02', 'sls_coin' => 'USD', 'sls_total' => 999]);

    $this->get(route('dashboard.ventas-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/VentasDiarias')
            ->where('sales.total', 2)
            ->where('sales.per_page', 15)
            ->where('sales.last_page', 1)
            ->has('sales.data', 2)
            ->where('sales.data.0.sls_coin', 'COP')
            ->where('sales.data.0.sls_total', 5000)
            ->has('sales.data.0.sls_number')
            ->has('sales.data.0.sls_status')
            ->has('sales.data.0.sls_payment_condition')
            ->where('sales.data.1.sls_coin', 'USD')
            ->where('sales.data.1.customer.clt_name', 'Cliente A')
            ->where('from', '2026-08-01')
            ->where('to', '2026-08-31'));
});

test('ventas diarias page paginates sales 15 per page', function () {
    createSaleTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    SaleOrder::factory()->count(16)->facv()->create(['sls_date' => '2026-08-05']);

    $this->get(route('dashboard.ventas-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/VentasDiarias')
            ->where('sales.total', 16)
            ->where('sales.per_page', 15)
            ->where('sales.last_page', 2)
            ->has('sales.data', 15)
            ->where('sales.links.0.url', null));

    $this->get(route('dashboard.ventas-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31', 'page' => 2]))
        ->assertInertia(fn ($page) => $page
            ->where('sales.total', 16)
            ->has('sales.data', 1)
            ->where('sales.current_page', 2));
});

test('ventas diarias page excludes sales outside the selected range', function () {
    createSaleTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    SaleOrder::factory()->facv()->create(['sls_date' => '2026-07-31', 'sls_coin' => 'USD', 'sls_total' => 30]);

    $this->get(route('dashboard.ventas-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/VentasDiarias')
            ->where('sales.total', 0)
            ->has('sales.data', 0));
});
