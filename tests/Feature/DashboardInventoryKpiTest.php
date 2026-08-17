<?php

use App\Enum\Inventary\ProType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CostHistory;
use App\Models\Product;
use App\Models\Rate;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\Inventory\InventoryKpiRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->brand = Brand::factory()->create();
    $this->category = Category::factory()->create();

    Rate::factory()->create([
        'rate_date' => now()->toDateString(),
        'rate_usd' => 100.0000,
        'rate_cop' => 3500.0000,
        'rate_eur' => 1.0000,
        'rate_cop_bs' => 3.5000,
    ]);
});

function createInventoryProduct(string $code, string $name, Warehouse $warehouse, float $quantity, float $costRef = 0): Product
{
    $product = Product::factory()->create([
        'pro_code' => $code,
        'pro_name' => $name,
        'pro_cat_id' => test()->category->id,
        'pro_bra_id' => test()->brand->id,
        'pro_type' => ProType::SIMPLE->value,
        'pro_iva' => '16',
    ]);

    $stock = new Stock;
    $stock->stk_pro_code = $code;
    $stock->stk_war_id = $warehouse->id;
    $stock->stk_cant = $quantity;
    $stock->save();

    if ($costRef > 0) {
        CostHistory::create([
            'cost_pro_code' => $code,
            'cost_pro_cost' => $costRef,
            'cost_pro_cost_ref' => $costRef,
            'cost_valorcam' => 1.0,
            'cost_currency' => 'USD',
            'cost_date' => now()->toDateString(),
            'cost_user_id' => test()->user->id,
        ]);
    }

    return $product;
}

test('guests are redirected from inventory dashboard pages', function () {
    auth()->logout();

    $this->get(route('dashboard.inventario'))->assertRedirect(route('login'));
    $this->get(route('dashboard.inventario.existencia'))->assertRedirect(route('login'));
    $this->get(route('dashboard.inventario.valor'))->assertRedirect(route('login'));
});

test('dashboard inventario card shows inventory value by currency', function () {
    $warehouse = Warehouse::factory()->create();
    createInventoryProduct('P001', 'Producto A', $warehouse, 10, 5);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('categoryAggregates.inventario.total.USD', 50)
            ->where('categoryAggregates.inventario.total.COP', 175000)
            ->where('categoryAggregates.inventario.total.BS', 5000));
});

test('inventario page lists existence by warehouse including empty ones', function () {
    $warehouseA = Warehouse::factory()->create(['war_name' => 'Principal']);
    $warehouseB = Warehouse::factory()->create(['war_name' => 'Secundario']);
    createInventoryProduct('P001', 'Producto A', $warehouseA, 10, 5);

    $this->get(route('dashboard.inventario'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Inventario')
            ->has('kpis.stockByWarehouse', 2)
            ->where('kpis.stockByWarehouse.0.war_name', 'Principal')
            ->where('kpis.stockByWarehouse.0.total_quantity', 10)
            ->where('kpis.stockByWarehouse.0.product_count', 1)
            ->where('kpis.stockByWarehouse.1.war_name', 'Secundario')
            ->where('kpis.stockByWarehouse.1.total_quantity', 0)
            ->where('kpis.stockByWarehouse.1.product_count', 0)
            ->where('kpis.inventoryValue.USD', 50));
});

test('existencia almacen page lists stock detail per product and warehouse', function () {
    $warehouseA = Warehouse::factory()->create(['war_name' => 'Principal']);
    $warehouseB = Warehouse::factory()->create(['war_name' => 'Secundario']);
    createInventoryProduct('P001', 'Producto A', $warehouseA, 10, 5);
    createInventoryProduct('P002', 'Producto B', $warehouseB, 3, 2);

    $this->get(route('dashboard.inventario.existencia'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Inventario/ExistenciaAlmacen')
            ->has('stockDetail', 2)
            ->where('stockDetail.0.pro_code', 'P001')
            ->where('stockDetail.0.war_name', 'Principal')
            ->where('stockDetail.0.quantity', 10)
            ->where('stockDetail.1.pro_code', 'P002')
            ->where('stockDetail.1.war_name', 'Secundario')
            ->where('stockDetail.1.quantity', 3));
});

test('valor inventario page lists value detail per product', function () {
    $warehouse = Warehouse::factory()->create();
    createInventoryProduct('P001', 'Producto A', $warehouse, 10, 5);
    createInventoryProduct('P002', 'Producto B', $warehouse, 4, 2);

    $this->get(route('dashboard.inventario.valor'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Inventario/ValorInventario')
            ->has('valueDetail', 2)
            ->where('valueDetail.0.pro_code', 'P001')
            ->where('valueDetail.0.total_quantity', 10)
            ->where('valueDetail.0.unit_cost_ref', 5)
            ->where('valueDetail.0.total_value_ref', 50)
            ->where('valueDetail.1.pro_code', 'P002')
            ->where('inventoryValue.USD', 58));
});

test('inventory value uses the latest registered cost', function () {
    $warehouse = Warehouse::factory()->create();
    $product = createInventoryProduct('P001', 'Producto A', $warehouse, 10, 5);

    CostHistory::create([
        'cost_pro_code' => $product->pro_code,
        'cost_pro_cost' => 8,
        'cost_pro_cost_ref' => 8,
        'cost_valorcam' => 1.0,
        'cost_currency' => 'USD',
        'cost_date' => now()->addDay()->toDateString(),
        'cost_user_id' => $this->user->id,
    ]);

    $this->get(route('dashboard.inventario.valor'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Inventario/ValorInventario')
            ->where('valueDetail.0.total_value_ref', 80)
            ->where('inventoryValue.USD', 80));
});

test('stock quantities are serialized as numbers, not strings', function () {
    $warehouse = Warehouse::factory()->create(['war_name' => 'Principal']);
    createInventoryProduct('P001', 'Producto A', $warehouse, 10, 5);

    $repository = app(InventoryKpiRepository::class);

    $stockByWarehouse = $repository->stockByWarehouse();

    expect($stockByWarehouse[0]['total_quantity'])->toBeFloat()
        ->and($stockByWarehouse[0]['product_count'])->toBeInt()
        ->and($stockByWarehouse[0]['warehouse_id'])->toBeInt();

    $stockDetail = $repository->stockDetail();

    expect($stockDetail[0]['quantity'])->toBeFloat()
        ->and($stockDetail[0]['war_id'])->toBeInt();
});
