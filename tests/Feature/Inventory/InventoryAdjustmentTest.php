<?php

use App\Enum\Inventary\UndProduct;
use App\Models\AdjustmentReason;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\MovementInventory;
use App\Models\Product;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\Inventory\InventoryAdjustmentRepository;
use App\Services\Inventory\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->brand = Brand::factory()->create();
    $this->category = Category::factory()->create();
    $this->warehouse = Warehouse::factory()->create(['war_name' => 'Principal']);
    $this->otherWarehouse = Warehouse::factory()->create(['war_name' => 'Sucursal']);

    // El ajuste de inventario (INV) siempre resta: factor -1.
    $this->invType = TransactionType::factory()->create([
        'trans_type_code' => 'INV',
        'trans_type_description' => 'Ajuste de inventario',
        'trans_type_factor' => '-1',
    ]);

    $this->reason = AdjustmentReason::factory()->create(['adjr_name' => 'Merma']);
    $this->reasonTwo = AdjustmentReason::factory()->create(['adjr_name' => 'Vencido']);

    $this->product = Product::factory()->create([
        'pro_cat_id' => $this->category->id,
        'pro_bra_id' => $this->brand->id,
        'pro_und' => UndProduct::UND->value,
        'pro_cant_min' => 0,
    ]);

    $this->otherProduct = Product::factory()->create([
        'pro_cat_id' => $this->category->id,
        'pro_bra_id' => $this->brand->id,
        'pro_und' => UndProduct::UND->value,
        'pro_cant_min' => 0,
    ]);

    app(StockService::class)
        ->updateStock($this->product->pro_code, $this->warehouse->id, 100.0);

    $this->adjustment = null;
});

/**
 * Stock actual de un producto en un almacén.
 */
function stockOf(string $proCode, int $warehouseId): float
{
    return (float) DB::table('stocks')
        ->where('stk_pro_code', $proCode)
        ->where('stk_war_id', $warehouseId)
        ->value('stk_cant');
}

/**
 * Payload de un renglón de ajuste.
 *
 * @return array<string, mixed>
 */
function adjustmentItem(Product $product, Warehouse $warehouse, AdjustmentReason $reason, float $quantity): array
{
    return [
        'min_pro_code' => $product->pro_code,
        'min_war_id' => $warehouse->id,
        'min_quantity' => $quantity,
        'min_reason_id' => $reason->id,
    ];
}

/**
 * @param  array<int, array<string, mixed>>  $items
 * @return array<string, mixed>
 */
function adjustmentPayload(array $items, string $number = 'AJ-001'): array
{
    return [
        'inv_number' => $number,
        'inv_date' => '2026-01-15',
        'inv_notes' => 'Ajuste de prueba',
        'items' => $items,
    ];
}

// ---------------------------------------------------------------------------
// Renderizado
// ---------------------------------------------------------------------------

test('create page renders the catalog needed by the form', function () {
    $this->get(route('inventory.adjustments.create'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Inventory/Adjustments/Create')
                ->has('warehouses', 2)
                ->has('products')
                ->has('reasons', 2)
                ->where('products.0.stocks.0.cant', 100)
                ->where('products.0.pro_und', 'und')
        );
});

test('index only lists INV adjustments', function () {
    Inventory::factory()->create([
        'inv_number' => 'AJ-INV',
        'inv_trans_type_code' => 'INV',
    ]);

    TransactionType::factory()->create([
        'trans_type_code' => 'FACV',
        'trans_type_description' => 'Factura de venta',
        'trans_type_factor' => '-1',
    ]);

    Inventory::factory()->create([
        'inv_number' => 'FACV-1',
        'inv_trans_type_code' => 'FACV',
    ]);

    $this->get(route('inventory.adjustments.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Inventory/Adjustments/Index')
                ->has('adjustments.data', 1)
                ->where('adjustments.data.0.inv_number', 'AJ-INV')
        );
});

// ---------------------------------------------------------------------------
// Store
// ---------------------------------------------------------------------------

test('store creates the adjustment and subtracts stock', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 12.0),
    ]))
        ->assertRedirect(route('inventory.adjustments.index'))
        ->assertSessionHasNoErrors();

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();

    expect($adjustment->inv_trans_type_code)->toBe('INV')
        ->and($adjustment->movements)->toHaveCount(1)
        ->and($adjustment->movements->first()->min_factor)->toBe(-1)
        ->and($adjustment->movements->first()->min_reason_id)->toBe($this->reason->id);

    // 100 - 12 = 88
    expect(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(88.0);
});

test('store rejects an adjustment without items', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([]))
        ->assertSessionHasErrors('items');

    expect(Inventory::count())->toBe(0);
});

test('store rejects a duplicated adjustment number', function () {
    Inventory::factory()->create([
        'inv_number' => 'AJ-001',
        'inv_trans_type_code' => 'INV',
    ]);

    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 1.0),
    ]))->assertSessionHasErrors('inv_number');
});

test('store rejects an unknown reason', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        [
            'min_pro_code' => $this->product->pro_code,
            'min_war_id' => $this->warehouse->id,
            'min_quantity' => 1.0,
            'min_reason_id' => 9999,
        ],
    ]))->assertSessionHasErrors('items.0.min_reason_id');
});

test('store fails gracefully when the INV transaction type is missing', function () {
    $this->invType->delete();

    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 5.0),
    ]))->assertSessionHasErrors('error');

    expect(Inventory::count())->toBe(0)
        ->and(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(100.0);
});

// ---------------------------------------------------------------------------
// Update — regresión de stock
// ---------------------------------------------------------------------------

test('update reconciles the stock delta when a line quantity changes', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 10.0),
    ]));

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();
    $movementId = $adjustment->movements->first()->id;

    expect(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(90.0);

    $this->put(route('inventory.adjustments.update', $adjustment), adjustmentPayload([
        [
            'id' => $movementId,
            'min_pro_code' => $this->product->pro_code,
            'min_war_id' => $this->warehouse->id,
            'min_quantity' => 25.0,
            'min_reason_id' => $this->reason->id,
        ],
    ]))->assertRedirect(route('inventory.adjustments.index'));

    // 100 - 25 = 75 (no 100 - 10 - 25, ni doble descuento)
    expect(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(75.0);
});

test('update restores the stock of removed lines', function () {
    app(StockService::class)->updateStock($this->otherProduct->pro_code, $this->warehouse->id, 20.0);

    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 10.0),
        adjustmentItem($this->otherProduct, $this->warehouse, $this->reason, 4.0),
    ]));

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();
    $keptMovement = $adjustment->movements
        ->firstWhere('min_pro_code', $this->product->pro_code);

    $this->put(route('inventory.adjustments.update', $adjustment), adjustmentPayload([
        [
            'id' => $keptMovement->id,
            'min_pro_code' => $this->product->pro_code,
            'min_war_id' => $this->warehouse->id,
            'min_quantity' => 10.0,
            'min_reason_id' => $this->reason->id,
        ],
    ]))->assertRedirect(route('inventory.adjustments.index'));

    expect($adjustment->fresh()->movements)->toHaveCount(1)
        // La línea eliminada devuelve su stock: este es el bug del mass delete.
        ->and(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(90.0)
        ->and(stockOf($this->otherProduct->pro_code, $this->warehouse->id))->toBe(20.0);
});

test('update moves the stock when a line changes product', function () {
    app(StockService::class)
        ->updateStock($this->otherProduct->pro_code, $this->warehouse->id, 50.0);

    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 10.0),
    ]));

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();
    $movement = $adjustment->movements->first();

    $this->put(route('inventory.adjustments.update', $adjustment), adjustmentPayload([
        [
            'id' => $movement->id,
            'min_pro_code' => $this->otherProduct->pro_code,
            'min_war_id' => $this->warehouse->id,
            'min_quantity' => 10.0,
            'min_reason_id' => $this->reason->id,
        ],
    ]))->assertRedirect(route('inventory.adjustments.index'));

    // El producto original se restaura completo y el nuevo descuenta.
    expect(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(100.0)
        ->and(stockOf($this->otherProduct->pro_code, $this->warehouse->id))->toBe(40.0);
});

test('update moves the stock when a line changes warehouse', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 10.0),
    ]));

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();
    $movement = $adjustment->movements->first();

    $this->put(route('inventory.adjustments.update', $adjustment), adjustmentPayload([
        [
            'id' => $movement->id,
            'min_pro_code' => $this->product->pro_code,
            'min_war_id' => $this->otherWarehouse->id,
            'min_quantity' => 10.0,
            'min_reason_id' => $this->reason->id,
        ],
    ]))->assertRedirect(route('inventory.adjustments.index'));

    expect(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(100.0)
        ->and(stockOf($this->product->pro_code, $this->otherWarehouse->id))->toBe(-10.0);
});

test('update accepts changing only the reason without touching the stock', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 10.0),
    ]));

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();
    $movement = $adjustment->movements->first();

    $this->put(route('inventory.adjustments.update', $adjustment), adjustmentPayload([
        [
            'id' => $movement->id,
            'min_pro_code' => $this->product->pro_code,
            'min_war_id' => $this->warehouse->id,
            'min_quantity' => 10.0,
            'min_reason_id' => $this->reasonTwo->id,
        ],
    ]))->assertRedirect(route('inventory.adjustments.index'));

    expect($adjustment->fresh()->movements->first()->min_reason_id)
        ->toBe($this->reasonTwo->id)
        ->and(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(90.0);
});

test('update rejects renaming the adjustment number', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 10.0),
    ]));

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();
    $movement = $adjustment->movements->first();

    $this->put(
        route('inventory.adjustments.update', $adjustment),
        adjustmentPayload([
            [
                'id' => $movement->id,
                'min_pro_code' => $this->product->pro_code,
                'min_war_id' => $this->warehouse->id,
                'min_quantity' => 10.0,
                'min_reason_id' => $this->reason->id,
            ],
        ], 'AJ-999')
    )->assertSessionHasErrors('inv_number');

    // Ni el número ni el stock se mueven.
    expect($adjustment->fresh()->inv_number)->toBe('AJ-001')
        ->and($adjustment->fresh()->movements)->toHaveCount(1)
        ->and(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(90.0);
});

test('update does not double apply stock when nothing changes', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 10.0),
    ]));

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();
    $movementId = $adjustment->movements->first()->id;

    $this->put(route('inventory.adjustments.update', $adjustment), adjustmentPayload([
        [
            'id' => $movementId,
            'min_pro_code' => $this->product->pro_code,
            'min_war_id' => $this->warehouse->id,
            'min_quantity' => 10.0,
            'min_reason_id' => $this->reason->id,
        ],
    ]))->assertRedirect(route('inventory.adjustments.index'));

    expect(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(90.0);
});

test('update rejects adopting the number of another adjustment', function () {
    $other = Inventory::factory()->create([
        'inv_number' => 'AJ-777',
        'inv_trans_type_code' => 'INV',
    ]);

    $this->put(route('inventory.adjustments.update', $other), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 1.0),
    ], 'AJ-001'))->assertSessionHasErrors('inv_number');
});

// ---------------------------------------------------------------------------
// Destroy — regresión de stock
// ---------------------------------------------------------------------------

test('destroy deletes the adjustment and fully restores the stock', function () {
    $this->post(route('inventory.adjustments.store'), adjustmentPayload([
        adjustmentItem($this->product, $this->warehouse, $this->reason, 30.0),
    ]));

    $adjustment = Inventory::where('inv_number', 'AJ-001')->firstOrFail();

    expect(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(70.0)
        ->and(MovementInventory::count())->toBe(1);

    $this->delete(route('inventory.adjustments.destroy', $adjustment))
        ->assertRedirect(route('inventory.adjustments.index'));

    // Regresión del mass delete: el stock tiene que volver a 100.
    expect(Inventory::whereKey($adjustment->getKey())->exists())->toBeFalse()
        ->and(MovementInventory::count())->toBe(0)
        ->and(stockOf($this->product->pro_code, $this->warehouse->id))->toBe(100.0);
});

// ---------------------------------------------------------------------------
// Caché de razones
// ---------------------------------------------------------------------------

test('reasons are cached and the cache is invalidated when a reason changes', function () {
    Cache::flush();

    $repository = app(InventoryAdjustmentRepository::class);

    $first = $repository->reasons();
    $second = $repository->reasons();

    expect($first)->toHaveCount(2)
        ->and($second)->toBe($first)
        ->and(Cache::has(InventoryAdjustmentRepository::reasonsCacheKey()))->toBeTrue();

    AdjustmentReason::create([
        'adjr_code' => 'ROTO',
        'adjr_name' => 'Roto',
        'adjr_description' => 'Producto roto',
    ]);

    expect($repository->reasons())->toHaveCount(3);
});

test('the reasons cache key is scoped per tenant', function () {
    expect(InventoryAdjustmentRepository::reasonsCacheKey())
        ->toBe('inventory:adjustment_reasons.central');
});
