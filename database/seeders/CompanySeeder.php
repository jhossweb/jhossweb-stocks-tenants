<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            ['id' => 1],
            [
                'cmp_name' => 'Vidrialum C.A.',
                'cmp_rif' => 'J-12345678-9',
                'cmp_address' => 'Av. Principal, Sector Industrial, Caracas',
                'cmp_phone' => '+58 212 555 1234',
                'cmp_email' => 'contacto@vidrialum.com',
                'cmp_website' => null,
                'cmp_logo' => null,
            ]
        );

        Cache::forget(Company::CACHE_KEY);
    }
}
