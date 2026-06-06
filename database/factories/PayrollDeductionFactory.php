<?php

namespace Database\Factories;

use App\Models\Payroll;
use App\Models\PayrollDeduction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollDeduction>
 */
class PayrollDeductionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pde_payroll_id' => Payroll::factory(),
            'pde_deductible_type' => 'App\\Models\\EmployeeLoan',
            'pde_deductible_id' => 1,
            'pde_percentage' => fake()->randomFloat(2, 1, 20),
            'pde_amount' => fake()->randomFloat(2, 10000, 100000),
        ];
    }
}
