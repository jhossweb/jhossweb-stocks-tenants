<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssignPlanAction
{
    public function execute(Tenant $tenant, Plan $plan, array $options = []): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $options): Subscription {
            $startedAt = isset($options['started_at']) ? Carbon::parse($options['started_at']) : now();
            $trialDays = (int) ($options['trial_days'] ?? $plan->trial_days);
            $trialEndsAt = $trialDays > 0 ? $startedAt->copy()->addDays($trialDays) : null;
            $periodStart = $startedAt;
            $periodEnd = isset($options['current_period_end']) ? Carbon::parse($options['current_period_end']) : $startedAt->copy()->addMonth();
            $graceDays = (int) ($options['grace_days'] ?? 0);

            // Close existing active? not required, allow latest as active conceptually
            $subscription = new Subscription([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $trialDays > 0 ? 'trialing' : 'active',
                'started_at' => $startedAt->toDateString(),
                'trial_ends_at' => $trialEndsAt?->toDateString(),
                'current_period_start' => $periodStart->toDateString(),
                'current_period_end' => $periodEnd->toDateString(),
                'grace_days' => $graceDays,
            ]);
            $subscription->save();

            $tenant->status = 'active';
            $tenant->status_source = 'subscription';
            $tenant->suspended_reason = null;
            $tenant->suspended_at = null;
            $tenant->suspended_by = null;
            $tenant->plan = $plan->name;
            $tenant->save();

            TenantEvent::create([
                'tenant_id' => $tenant->id,
                'user_id' => Auth::id(),
                'event' => 'plan_assigned',
                'description' => "Plan {$plan->code} asignado",
                'meta' => ['plan_id' => $plan->id, 'subscription_id' => $subscription->id],
            ]);

            return $subscription;
        });
    }
}
