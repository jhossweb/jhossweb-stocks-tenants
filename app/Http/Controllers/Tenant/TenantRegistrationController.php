<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\CreateTenant;
use App\DTOs\Tenant\RegisterTenantData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RegisterTenantRequest;
use App\Models\Tenant;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TenantRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('register/CompanyCreate', [
            'domainSuffix' => config('tenancy.tenant_domain_suffix'),
        ]);
    }

    public function store(RegisterTenantRequest $request, CreateTenant $createTenant): SymfonyResponse
    {
        /** @var Tenant $tenant */
        $tenant = $createTenant->handle(RegisterTenantData::fromArray($request->validated()));

        $url = $this->tenantLoginUrl($tenant->domain());

        return Inertia::location($url);
    }

    private function tenantLoginUrl(?string $domain): string
    {
        $domain = $domain ?? 'tenant';

        // El puerto se toma de APP_URL para conservar el :8000 del `php artisan serve`.
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';
        $port = parse_url((string) config('app.url'), PHP_URL_PORT);

        return $port === null
            ? "{$scheme}://{$domain}/login"
            : "{$scheme}://{$domain}:{$port}/login";
    }
}
