<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enum\Tenancy\TenantModule;
use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! tenancy()->initialized) {
            abort(403, 'Este módulo no está incluido en tu plan.');
        }

        try {
            $tenantModule = TenantModule::from($module);
        } catch (\ValueError $e) {
            abort(403, 'Módulo inválido.');
        }

        if ($tenantModule->isCore()) {
            return $next($request);
        }

        $tenantId = tenant('id');
        if ($tenantId === null) {
            abort(403, 'Este módulo no está incluido en tu plan.');
        }

        $subscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->with('plan')
            ->latest()
            ->first();

        if ($subscription === null || $subscription->plan === null) {
            abort(403, 'Este módulo no está incluido en tu plan.');
        }

        $keys = $subscription->plan->moduleKeys();
        if (! in_array($tenantModule->value, $keys, true)) {
            abort(403, 'Este módulo no está incluido en tu plan.');
        }

        return $next($request);
    }
}
