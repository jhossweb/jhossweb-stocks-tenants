<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\TenantEvent;
use Illuminate\Console\Command;

class SyncSubscriptionsCommand extends Command
{
    protected $signature = 'saas:subscriptions:sync';

    protected $description = 'Sincroniza estados de suscripciones con tenants';

    public function handle(): int
    {
        $subs = Subscription::with('tenant')->get();

        foreach ($subs as $sub) {
            $tenant = $sub->tenant;
            if (! $tenant) {
                continue;
            }
            if ($tenant->hasManualSuspension()) {
                continue;
            }

            $now = now();

            if (in_array($sub->status, ['trialing', 'active'], true)) {
                $tenant->status = 'active';
                $tenant->save();

                continue;
            }

            if ($sub->status === 'past_due' && $sub->current_period_end) {
                $graceUntil = $sub->current_period_end->copy()->addDays((int) $sub->grace_days);
                if ($now->lte($graceUntil)) {
                    $tenant->status = 'active';
                    $tenant->save();

                    continue;
                }
            }

            if (in_array($sub->status, ['past_due', 'canceled', 'expired', 'suspended'], true)) {
                if ($tenant->status !== 'suspended') {
                    $tenant->status = 'suspended';
                    $tenant->status_source = 'system';
                    $tenant->save();
                    TenantEvent::create([
                        'tenant_id' => $tenant->id,
                        'event' => 'status_changed',
                        'description' => "Sincronización: tenant pasado a suspended por subscription {$sub->status}",
                        'meta' => ['subscription_id' => $sub->id, 'status' => $sub->status],
                    ]);
                }
            }
        }

        return self::SUCCESS;
    }
}
