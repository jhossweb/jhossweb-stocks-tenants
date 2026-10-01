<?php

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSaleTransactionTypes(): void
{
    TransactionType::create(['trans_type_code' => 'FACV', 'trans_type_description' => 'Factura Venta', 'trans_type_factor' => '-1']);
    TransactionType::create(['trans_type_code' => 'DEVV', 'trans_type_description' => 'Devolucion Venta', 'trans_type_factor' => '1']);
}

function createPurchaseTransactionTypes(): void
{
    TransactionType::create(['trans_type_code' => 'FACP', 'trans_type_description' => 'Factura Compra', 'trans_type_factor' => '1']);
    Supplier::factory()->create();
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

test('compras diarias card shows today purchases by currency', function () {
    createPurchaseTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    // Fecha fija a mitad de mes: el test siembra también una compra de "ayer",
    // que debe seguir dentro del rango mensual por defecto. Sin congelar el
    // reloj, el día 1 de cada mes esa compra cae en el mes anterior y el
    // test falla por calendario, no por la consulta.
    $this->travelTo(now()->startOfMonth()->addDays(14));

    $today = now()->format('Y-m-d');

    PurchaseOrder::factory()->create(['invoice_date' => $today, 'invoice_coin' => 'USD', 'invoice_total' => 50, 'invoice_status' => 'paid']);
    PurchaseOrder::factory()->create(['invoice_date' => $today, 'invoice_coin' => 'COP', 'invoice_total' => 1000, 'invoice_status' => 'partial']);
    PurchaseOrder::factory()->create(['invoice_date' => now()->subDay()->format('Y-m-d'), 'invoice_coin' => 'USD', 'invoice_total' => 999, 'invoice_status' => 'paid']);

    $this->get(route('dashboard.compras-diarias'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/ComprasDiarias')
            ->where('today.USD', 50)
            ->where('today.COP', 1000)
            ->where('purchases.total', 3));
});

test('compras diarias page lists purchases detail paginated', function () {
    createPurchaseTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    $supplier = Supplier::factory()->create(['supplier_name' => 'Proveedor A']);
    PurchaseOrder::factory()->create([
        'invoice_number' => 'FACP-0001',
        'invoice_date' => '2026-08-01',
        'invoice_supplier_id' => $supplier->id,
        'invoice_coin' => 'USD',
        'invoice_total' => 100.5,
        'invoice_status' => 'paid',
        'invoice_payment_condition' => 'credit',
    ]);
    PurchaseOrder::factory()->create([
        'invoice_number' => 'FACP-0002',
        'invoice_date' => '2026-08-03',
        'invoice_coin' => 'COP',
        'invoice_total' => 5000,
        'invoice_status' => 'pending',
        'invoice_payment_condition' => 'cash',
    ]);

    $this->get(route('dashboard.compras-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/ComprasDiarias')
            ->where('purchases.total', 2)
            ->where('purchases.per_page', 15)
            ->where('purchases.last_page', 1)
            ->has('purchases.data', 2)
            ->where('purchases.data.0.invoice_coin', 'COP')
            ->where('purchases.data.0.invoice_total', 5000)
            ->where('purchases.data.0.invoice_number', 'FACP-0002')
            ->has('purchases.data.0.invoice_status')
            ->has('purchases.data.0.invoice_payment_condition')
            ->where('purchases.data.1.invoice_coin', 'USD')
            ->where('purchases.data.1.supplier.supplier_name', 'Proveedor A')
            ->where('from', '2026-08-01')
            ->where('to', '2026-08-31'));
});

test('compras diarias page paginates purchases 15 per page', function () {
    createPurchaseTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    PurchaseOrder::factory()->count(16)->create(['invoice_date' => '2026-08-05', 'invoice_status' => 'paid']);

    $this->get(route('dashboard.compras-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/ComprasDiarias')
            ->where('purchases.total', 16)
            ->where('purchases.per_page', 15)
            ->where('purchases.last_page', 2)
            ->has('purchases.data', 15)
            ->where('purchases.links.0.url', null));

    $this->get(route('dashboard.compras-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31', 'page' => 2]))
        ->assertInertia(fn ($page) => $page
            ->where('purchases.total', 16)
            ->has('purchases.data', 1)
            ->where('purchases.current_page', 2));
});

test('compras diarias page excludes purchases outside range and cancelled', function () {
    createPurchaseTransactionTypes();

    $user = User::factory()->create();
    $this->actingAs($user);

    PurchaseOrder::factory()->create(['invoice_date' => '2026-07-31', 'invoice_coin' => 'USD', 'invoice_total' => 30, 'invoice_status' => 'paid']);
    PurchaseOrder::factory()->create(['invoice_date' => '2026-08-10', 'invoice_coin' => 'USD', 'invoice_total' => 50, 'invoice_status' => 'cancelled']);

    $this->get(route('dashboard.compras-diarias', ['from' => '2026-08-01', 'to' => '2026-08-31']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/ComprasDiarias')
            ->where('purchases.total', 0)
            ->has('purchases.data', 0));
});
