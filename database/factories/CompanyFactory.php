<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 1,
            'cmp_name' => 'Vidrialum C.A.',
            'cmp_rif' => 'J-12345678-9',
            'cmp_address' => 'Av. Principal, Sector Industrial, Caracas',
            'cmp_phone' => '+58 212 555 1234',
            'cmp_email' => 'contacto@vidrialum.com',
            'cmp_website' => null,
            'cmp_logo' => null,
        ];
    }
}
