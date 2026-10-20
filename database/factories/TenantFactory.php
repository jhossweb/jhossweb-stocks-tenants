<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Tenancy\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'id' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name' => $name,
            'status' => TenantStatus::ACTIVE->value,
            'plan' => 'standard',
            'rif' => fake()->numerify('J-########-#'),
            'address' => fake()->address(),
            'phone' => fake()->numerify('04##-###-####'),
            'admin_email' => fake()->unique()->safeEmail(),
            'admin_name' => fake()->name(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => TenantStatus::PENDING->value]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => TenantStatus::SUSPENDED->value]);
    }
}
