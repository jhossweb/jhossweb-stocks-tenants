<?php

namespace Database\Factories;

use App\Enum\Inventary\UndProduct;
use App\Enum\TypeIva;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pro_code' => fake()->unique()->word,
            'pro_name' => fake()->name,
            'pro_und' => fake()->randomElement(UndProduct::values()),
            'pro_iva' => fake()->randomElement(TypeIva::values()),
            'pro_cat_id' => Category::inRandomOrder()->first()->id,
            'pro_bra_id' => Brand::inRandomOrder()->first()->id,
        ];
    }
}
