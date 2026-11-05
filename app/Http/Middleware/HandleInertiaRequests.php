<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\Finance\RateService;
use App\Services\Settings\UserAccessNames;
use App\Services\Tenancy\TenantPlanResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $isTenant = tenancy()->initialized;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => function () use ($request): ?array {
                    $user = $request->user();

                    if ($user === null) {
                        return null;
                    }

                    $access = app(UserAccessNames::class)->for($user);

                    return [
                        ...$user->toArray(),
                        'roles' => $access['roles'],
                        'permissions' => $access['permissions'],
                    ];
                },
            ],
            // Solo disponibles cuando un tenant está inicializado
            // (en el portal central quedan como null).
            'company' => fn () => $isTenant ? Company::current() : null,
            'tenant' => fn () => $isTenant
                ? [
                    'id' => tenant('id'),
                    'domain' => tenant()->domains->first()?->domain,
                    'features' => app(TenantPlanResolver::class)->currentPlan()?->featureStates() ?? [],
                ]
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Aviso de tasa de cambio ausente/vencida. Solo en tenant: en el
            // portal central la tabla `rates` no existe en el schema public.
            'rateNotice' => fn () => $isTenant
                ? app(RateService::class)->notice()?->toArray()
                : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'payment_pending' => $request->session()->get('payment_pending'),
                'quote_availability' => $request->session()->get('quote_availability'),
            ],
        ];
    }
}
