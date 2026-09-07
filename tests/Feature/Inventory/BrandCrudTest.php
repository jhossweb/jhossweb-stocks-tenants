<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('index page renders the brands list', function () {
    Brand::factory()->count(3)->create();

    $this->get(route('brands.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Inventory/Brands/Index')
                ->has('brands', 3)
        );
});

test('store creates a brand', function () {
    $this->post(route('brands.store'), ['bra_name' => 'Pilkington'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Brand::where('bra_name', 'Pilkington')->exists())->toBeTrue();
});

test('store rejects a duplicated brand name', function () {
    Brand::create(['bra_name' => 'Pilkington']);

    $this->from(route('brands.index'))
        ->post(route('brands.store'), ['bra_name' => 'Pilkington'])
        ->assertRedirect(route('brands.index'))
        ->assertSessionHasErrors('bra_name');

    expect(Brand::count())->toBe(1);
});

test('update modifies a brand name', function () {
    $brand = Brand::create(['bra_name' => 'Nombre Viejo']);

    $this->put(route('brands.update', $brand), ['bra_name' => 'Nombre Nuevo'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($brand->fresh()->bra_name)->toBe('Nombre Nuevo');
});

test('update allows keeping the same name for the same brand', function () {
    $brand = Brand::create(['bra_name' => 'Vitro']);

    $this->put(route('brands.update', $brand), ['bra_name' => 'Vitro'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Brand::count())->toBe(1);
});

test('destroy deletes a brand without products', function () {
    $brand = Brand::create(['bra_name' => 'Sin Uso']);

    $this->delete(route('brands.destroy', $brand))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Brand::find($brand->id))->toBeNull();
});

test('destroy blocks a brand that has products', function () {
    $brand = Brand::create(['bra_name' => 'Con Productos']);
    $category = Category::factory()->create();
    Product::factory()->create([
        'pro_cat_id' => $category->id,
        'pro_bra_id' => $brand->id,
    ]);

    $this->from(route('brands.index'))
        ->delete(route('brands.destroy', $brand))
        ->assertRedirect(route('brands.index'))
        ->assertSessionHasErrors('error');

    expect(Brand::find($brand->id))->not->toBeNull();
});
