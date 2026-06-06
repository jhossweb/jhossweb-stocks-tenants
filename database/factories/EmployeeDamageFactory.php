<?php

namespace Database\Factories;

use App\Enum\Payroll\DamageStatus;
use App\Enum\TypeCoin;
use App\Models\Employee;
use App\Models\EmployeeDamage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDamage>
 */
class EmployeeDamageFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 20000, 500000);

        return [
            'eda_employee_id' => Employee::factory(),
            'eda_amount' => $amount,
            'eda_remaining_balance' => $amount,
            'eda_coin' => TypeCoin::COP,
            'eda_valorcam' => 1,
            'eda_description' => fake()->randomElement([
                'Vidrio roto durante instalación',
                'Herramienta dañada',
                'Perfil de aluminio desperdiciado',
                'Equipo de corte averiado',
                'Espejo quebrado en manipulación',
            ]),
            'eda_status' => DamageStatus::ACTIVE,
        ];
    }
}
