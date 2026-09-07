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

test('index page renders the categories list', function () {
    Category::factory()->count(3)->create();

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Inventory/Categories/Index')
                ->has('categories', 3)
        );
});

test('store creates a category', function () {
    $this->post(route('categories.store'), ['cat_name' => 'Vidrio Templado'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Category::where('cat_name', 'Vidrio Templado')->exists())->toBeTrue();
});

test('store rejects a duplicated category name', function () {
    Category::create(['cat_name' => 'Vidrio Templado']);

    $this->from(route('categories.index'))
        ->post(route('categories.store'), ['cat_name' => 'Vidrio Templado'])
        ->assertRedirect(route('categories.index'))
        ->assertSessionHasErrors('cat_name');

    expect(Category::count())->toBe(1);
});

test('update modifies a category name', function () {
    $category = Category::create(['cat_name' => 'Nombre Viejo']);

    $this->put(route('categories.update', $category), ['cat_name' => 'Nombre Nuevo'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($category->fresh()->cat_name)->toBe('Nombre Nuevo');
});

test('update allows keeping the same name for the same category', function () {
    $category = Category::create(['cat_name' => 'Vidrio']);

    $this->put(route('categories.update', $category), ['cat_name' => 'Vidrio'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Category::count())->toBe(1);
});

test('destroy deletes a category without products', function () {
    $category = Category::create(['cat_name' => 'Sin Uso']);

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Category::find($category->id))->toBeNull();
});

test('destroy blocks a category that has products', function () {
    $category = Category::create(['cat_name' => 'Con Productos']);
    $brand = Brand::factory()->create();
    Product::factory()->create([
        'pro_cat_id' => $category->id,
        'pro_bra_id' => $brand->id,
    ]);

    $this->from(route('categories.index'))
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'))
        ->assertSessionHasErrors('error');

    expect(Category::find($category->id))->not->toBeNull();
});
