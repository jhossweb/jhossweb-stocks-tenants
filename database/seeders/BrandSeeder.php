<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['bra_name' => 'Pilkington'],
            ['bra_name' => 'Saint-Gobain'],
            ['bra_name' => 'Guardian Glass'],
            ['bra_name' => 'Vitro'],
            ['bra_name' => 'Cortizo'],
            ['bra_name' => 'Aluvisa'],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
