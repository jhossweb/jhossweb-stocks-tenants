<?php

use App\Enum\Inventary\UndProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $this->warehouse = Warehouse::factory()->create();

    $this->product = Product::factory()->create([
        'pro_cat_id' => $category->id,
        'pro_bra_id' => $brand->id,
        'pro_und' => UndProduct::UND->value,
        'pro_cant_min' => 10,
    ]);
});

test('updateStock creates the stock row when it does not exist', function () {
    $service = app(StockService::class);

    $service->updateStock($this->product->pro_code, $this->warehouse->id, 25.0);

    $this->assertDatabaseHas('stocks', [
        'stk_pro_code' => $this->product->pro_code,
        'stk_war_id' => $this->warehouse->id,
    ]);

    expect((float) DB::table('stocks')
        ->where('stk_pro_code', $this->product->pro_code)
        ->where('stk_war_id', $this->warehouse->id)
        ->value('stk_cant'))->toBe(25.0);
});

test('updateStock accumulates on an existing stock row', function () {
    $service = app(StockService::class);

    $service->updateStock($this->product->pro_code, $this->warehouse->id, 10.0);
    $service->updateStock($this->product->pro_code, $this->warehouse->id, 5.0);

    expect((float) DB::table('stocks')
        ->where('stk_pro_code', $this->product->pro_code)
        ->where('stk_war_id', $this->warehouse->id)
        ->value('stk_cant'))->toBe(15.0);
});

test('checkMinStock returns null when stock is above the minimum', function () {
    $service = app(StockService::class);
    $service->updateStock($this->product->pro_code, $this->warehouse->id, 20.0);

    expect($service->checkMinStock($this->product->pro_code, $this->warehouse->id))->toBeNull();
});

test('checkMinStock returns null when the product has no minimum stock', function () {
    $this->product->update(['pro_cant_min' => 0]);

    $service = app(StockService::class);
    $service->updateStock($this->product->pro_code, $this->warehouse->id, 1.0);

    expect($service->checkMinStock($this->product->pro_code, $this->warehouse->id))->toBeNull();
});

test('checkMinStock returns null when there is no stock row', function () {
    $service = app(StockService::class);

    expect($service->checkMinStock($this->product->pro_code, $this->warehouse->id))->toBeNull();
});

test('checkMinStock returns the low stock data when at or below the minimum', function () {
    $service = app(StockService::class);
    $service->updateStock($this->product->pro_code, $this->warehouse->id, 10.0);

    expect($service->checkMinStock($this->product->pro_code, $this->warehouse->id))
        ->toBe([
            'pro_name' => $this->product->pro_name,
            'stk_cant' => 10.0,
            'pro_und' => UndProduct::UND->value,
        ]);
});

test('addLowStockWarning accumulates warnings without side effects', function () {
    $service = app(StockService::class);

    $service->addLowStockWarning($this->product->pro_code, $this->warehouse->id);

    expect($service->lowStockWarnings())->toBeEmpty();

    $service->updateStock($this->product->pro_code, $this->warehouse->id, 5.0);
    $service->addLowStockWarning($this->product->pro_code, $this->warehouse->id);

    expect($service->lowStockWarnings())->toHaveCount(1);
    expect($service->lowStockWarnings()[0]['pro_name'])->toBe($this->product->pro_name);
});

test('getConversionFactor returns the conversion factor for mts, kg and m2', function () {
    foreach (['mts', 'kg', 'm2'] as $unit) {
        $product = Product::factory()->create([
            'pro_cat_id' => $this->product->pro_cat_id,
            'pro_bra_id' => $this->product->pro_bra_id,
            'pro_und' => $unit,
            'pro_conversion_factor' => 100.0,
        ]);

        expect(app(StockService::class)->getConversionFactor($product->pro_code))->toBe(100.0);
    }
});

test('getConversionFactor returns 1 for non-convertible units', function () {
    expect(app(StockService::class)->getConversionFactor($this->product->pro_code))->toBe(1.0);
});
