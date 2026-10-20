<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Tenancy\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now();

        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active->value,
            'started_at' => $start->toDateString(),
            'trial_ends_at' => null,
            'current_period_start' => $start->toDateString(),
            'current_period_end' => $start->copy()->addMonth()->toDateString(),
            'canceled_at' => null,
            'grace_days' => 0,
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::Trialing->value,
            'trial_ends_at' => now()->addDays(14)->toDateString(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::Canceled->value,
            'canceled_at' => now()->toDateString(),
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->id]);
    }

    public function forPlan(Plan $plan): static
    {
        return $this->state(fn (): array => ['plan_id' => $plan->id]);
    }
}
