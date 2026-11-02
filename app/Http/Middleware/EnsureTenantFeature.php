<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enum\Tenancy\TenantFeature;
use App\Services\Tenancy\TenantPlanResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantFeature
{
    public function __construct(
        private readonly TenantPlanResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        try {
            $tenantFeature = TenantFeature::from($feature);
        } catch (\ValueError) {
            abort(403, 'Funcionalidad inválida.');
        }

        if (! $this->resolver->allows($tenantFeature)) {
            abort(403, 'Esta funcionalidad no está incluida en tu plan.');
        }

        return $next($request);
    }
}
