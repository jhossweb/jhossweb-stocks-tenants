<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('index page renders the warehouses list', function () {
    Warehouse::factory()->count(3)->create();

    $this->get(route('warehouses.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Inventory/Warehouses/Index')
                ->has('warehouses', 3)
        );
});

test('store creates a warehouse', function () {
    $this->post(route('warehouses.store'), ['war_name' => 'Almacén Principal'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Warehouse::where('war_name', 'Almacén Principal')->exists())->toBeTrue();
});

test('store rejects a duplicated warehouse name', function () {
    Warehouse::create(['war_name' => 'Almacén Principal']);

    $this->from(route('warehouses.index'))
        ->post(route('warehouses.store'), ['war_name' => 'Almacén Principal'])
        ->assertRedirect(route('warehouses.index'))
        ->assertSessionHasErrors('war_name');

    expect(Warehouse::count())->toBe(1);
});

test('update modifies a warehouse name', function () {
    $warehouse = Warehouse::create(['war_name' => 'Nombre Viejo']);

    $this->put(route('warehouses.update', $warehouse), ['war_name' => 'Nombre Nuevo'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($warehouse->fresh()->war_name)->toBe('Nombre Nuevo');
});

test('update allows keeping the same name for the same warehouse', function () {
    $warehouse = Warehouse::create(['war_name' => 'Bodega Norte']);

    $this->put(route('warehouses.update', $warehouse), ['war_name' => 'Bodega Norte'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Warehouse::count())->toBe(1);
});

test('destroy deletes a warehouse without stock or movements', function () {
    $warehouse = Warehouse::create(['war_name' => 'Sin Uso']);

    $this->delete(route('warehouses.destroy', $warehouse))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Warehouse::find($warehouse->id))->toBeNull();
});

test('destroy blocks a warehouse that has stock registered', function () {
    $warehouse = Warehouse::create(['war_name' => 'Con Stock']);

    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $product = Product::factory()->create([
        'pro_cat_id' => $category->id,
        'pro_bra_id' => $brand->id,
    ]);

    $stock = new Stock;
    $stock->stk_pro_code = $product->pro_code;
    $stock->stk_war_id = $warehouse->id;
    $stock->stk_cant = 10;
    $stock->save();

    $this->from(route('warehouses.index'))
        ->delete(route('warehouses.destroy', $warehouse))
        ->assertRedirect(route('warehouses.index'))
        ->assertSessionHasErrors('error');

    expect(Warehouse::find($warehouse->id))->not->toBeNull();
});
