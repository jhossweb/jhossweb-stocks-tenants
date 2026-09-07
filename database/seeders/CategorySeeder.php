<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['cat_name' => 'Vidrio Templado'],
            ['cat_name' => 'Vidrio Laminado'],
            ['cat_name' => 'Vidrio Crudo'],
            ['cat_name' => 'Espejos'],
            ['cat_name' => 'Perfiles de Aluminio'],
            ['cat_name' => 'Herrajes y Accesorios'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
