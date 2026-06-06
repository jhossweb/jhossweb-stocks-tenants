<?php

namespace Database\Factories;

use App\Enum\Payroll\LoanStatus;
use App\Enum\TypeCoin;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeLoan>
 */
class EmployeeLoanFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50000, 2000000);

        return [
            'elo_employee_id' => Employee::factory(),
            'elo_amount' => $amount,
            'elo_remaining_balance' => $amount,
            'elo_coin' => TypeCoin::COP,
            'elo_valorcam' => 1,
            'elo_description' => fake()->sentence(),
            'elo_status' => LoanStatus::ACTIVE,
        ];
    }
}
