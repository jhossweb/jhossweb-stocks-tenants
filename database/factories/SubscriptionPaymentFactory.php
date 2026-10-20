<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Tenancy\Currency;
use App\Enum\Tenancy\PaymentMethod;
use App\Enum\Tenancy\PaymentStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 20, 200);

        return [
            'subscription_id' => Subscription::factory(),
            'tenant_id' => Tenant::factory(),
            'receipt_number' => 'REC-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'amount' => $amount,
            'currency' => Currency::USD->value,
            'exchange_rate' => 1,
            'amount_usd' => $amount,
            'method' => PaymentMethod::TRANSFER->value,
            'reference' => fake()->optional()->bothify('REF-####'),
            'paid_at' => now()->toDateString(),
            'status' => PaymentStatus::PAID->value,
            'notes' => null,
        ];
    }

    public function voided(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::VOIDED->value,
            'voided_at' => now(),
        ]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (): array => [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }

    public function forPlan(Plan $plan): static
    {
        return $this->state(fn (): array => [
            'amount' => (float) $plan->price_usd,
            'amount_usd' => (float) $plan->price_usd,
        ]);
    }
}
