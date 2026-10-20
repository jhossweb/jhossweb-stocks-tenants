<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Actions\Tenancy\AssignPlanAction;
use App\DTOs\Tenancy\SubscriptionData;
use App\Enum\Tenancy\BillingCycle;
use App\Enum\Tenancy\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;

final class SubscriptionService
{
    public function __construct(
        private readonly AssignPlanAction $assignPlan,
    ) {}

    /**
     * Asignar un plan = crear su inscripción. Reutiliza AssignPlanAction para
     * crear la suscripción, activar el tenant y registrar el evento.
     */
    public function create(SubscriptionData $data): Subscription
    {
        $tenant = Tenant::query()->findOrFail($data->tenant_id);
        $plan = Plan::query()->findOrFail($data->plan_id);

        $options = array_filter([
            'started_at' => $data->started_at?->format('Y-m-d'),
            'current_period_end' => $data->current_period_end?->format('Y-m-d'),
            'grace_days' => $data->grace_days,
        ], fn ($value): bool => $value !== null);

        return $this->assignPlan->execute($tenant, $plan, $options);
    }

    /**
     * Asigna un plan a un tenant existente (crea la suscripción y lo activa).
     */
    public function assign(Tenant $tenant, Plan $plan): Subscription
    {
        return $this->assignPlan->execute($tenant, $plan);
    }

    public function update(Subscription $subscription, SubscriptionData $data): Subscription
    {
        $subscription->plan_id = $data->plan_id;

        if ($data->status !== null) {
            $subscription->status = $data->status;
        }

        if ($data->started_at !== null) {
            $subscription->started_at = $data->started_at;
        }

        if ($data->trial_ends_at !== null) {
            $subscription->trial_ends_at = $data->trial_ends_at;
        }

        if ($data->current_period_start !== null) {
            $subscription->current_period_start = $data->current_period_start;
        }

        if ($data->current_period_end !== null) {
            $subscription->current_period_end = $data->current_period_end;
        }

        $subscription->grace_days = $data->grace_days;
        $subscription->save();

        return $subscription->refresh();
    }

    public function renew(Subscription $subscription): Subscription
    {
        $subscription->loadMissing('plan');

        $base = $subscription->current_period_end && $subscription->current_period_end->isFuture()
            ? $subscription->current_period_end->copy()
            : now();

        $end = match ($subscription->plan?->billing_cycle) {
            BillingCycle::Yearly->value => $base->copy()->addYear(),
            BillingCycle::Quarterly->value => $base->copy()->addMonths(3),
            default => $base->copy()->addMonth(),
        };

        $subscription->status = SubscriptionStatus::Active->value;
        $subscription->current_period_start = $base->toDateString();
        $subscription->current_period_end = $end->toDateString();
        $subscription->canceled_at = null;
        $subscription->save();

        return $subscription->refresh();
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->status = SubscriptionStatus::Canceled->value;
        $subscription->canceled_at = now()->toDateString();
        $subscription->save();

        return $subscription->refresh();
    }

    public function delete(Subscription $subscription): bool
    {
        return (bool) $subscription->delete();
    }
}
