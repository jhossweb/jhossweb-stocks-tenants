<?php

namespace Database\Factories;

use App\Enum\Payroll\PayrollPeriodStatus;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->date();
        $end = fake()->dateTimeBetween($start, '+7 days')->format('Y-m-d');

        return [
            'pp_start_date' => $start,
            'pp_end_date' => $end,
            'pp_payment_date' => fake()->dateTimeBetween($end, '+3 days')->format('Y-m-d'),
            'pp_status' => PayrollPeriodStatus::PENDING,
        ];
    }
}
