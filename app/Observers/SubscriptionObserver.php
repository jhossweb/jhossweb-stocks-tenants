<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Subscription;
use App\Models\TenantEvent;
use Illuminate\Support\Facades\Auth;

class SubscriptionObserver
{
    public function updated(Subscription $subscription): void
    {
        TenantEvent::create([
            'tenant_id' => $subscription->tenant_id,
            'user_id' => Auth::id(),
            'event' => 'status_changed',
            'description' => "Subscription #{$subscription->id} status changed to {$subscription->status}",
            'meta' => ['subscription_id' => $subscription->id, 'status' => $subscription->status],
        ]);
    }
}
