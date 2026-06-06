<?php

namespace Database\Factories;

use App\Enum\Payroll\PayrollStatus;
use App\Enum\TypeCoin;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    public function definition(): array
    {
        $salary = fake()->randomFloat(2, 600000, 1800000);

        return [
            'pay_employee_id' => Employee::factory(),
            'pay_period_id' => PayrollPeriod::factory(),
            'pay_base_salary' => $salary,
            'pay_coin' => TypeCoin::COP,
            'pay_valorcam' => 1,
            'pay_total_deductions' => 0,
            'pay_net_salary' => $salary,
            'pay_status' => PayrollStatus::PENDING,
        ];
    }
}
