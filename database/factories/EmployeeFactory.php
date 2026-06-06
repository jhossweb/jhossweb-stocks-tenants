<?php

namespace Database\Factories;

use App\Enum\Employees\EmployeeStatus;
use App\Enum\TypeCoin;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'emp_name' => fake()->name(),
            'emp_document' => fake()->unique()->numerify('##########'),
            'emp_phone' => fake()->phoneNumber(),
            'emp_email' => fake()->unique()->safeEmail(),
            'emp_address' => fake()->address(),
            'emp_position' => fake()->randomElement(['Jefe de Taller', 'Instalador', 'Cortador de Vidrio', 'Ayudante', 'Diseñador']),
            'emp_hire_date' => fake()->date(),
            'emp_base_salary' => fake()->randomFloat(2, 600000, 1800000),
            'emp_salary_coin' => TypeCoin::COP,
            'emp_status' => EmployeeStatus::ACTIVE,
        ];
    }
}
