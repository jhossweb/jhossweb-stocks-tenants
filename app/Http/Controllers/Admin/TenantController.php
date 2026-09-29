<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tenant\CreateTenant;
use App\DTOs\Tenant\RegisterTenantData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/tenants/index', [
            'tenants' => Tenant::query()
                ->with('domains')
                ->latest()
                ->get()
                ->map(fn (Tenant $tenant) => $this->present($tenant)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tenants/create', [
            'domainSuffix' => config('tenancy.tenant_domain_suffix'),
        ]);
    }

    public function store(StoreTenantRequest $request, CreateTenant $createTenant): RedirectResponse
    {
        $tenant = $createTenant->handle(new RegisterTenantData(
            companyName: (string) $request->validated('company_name'),
            adminName: (string) $request->validated('admin_name'),
            adminEmail: (string) $request->validated('admin_email'),
            adminPassword: (string) $request->validated('admin_password'),
            slug: $request->validated('slug'),
            plan: $request->validated('plan') ?? 'standard',
            status: $request->validated('status') ?? Tenant::STATUS_ACTIVE,
        ));

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant [{$tenant->id}] creado.");
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('admin/tenants/edit', [
            'tenant' => $this->present($tenant),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update([
            'name' => $request->validated('name'),
            'plan' => $request->validated('plan'),
            'status' => $request->validated('status'),
        ]);

        return back()->with('success', "Tenant [{$tenant->id}] actualizado.");
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenantId = $tenant->id;
        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant [{$tenantId}] eliminado.");
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'plan' => $tenant->plan,
            'status' => $tenant->status,
            'domain' => $tenant->domain(),
            'created_at' => $tenant->created_at?->toDateTimeString(),
        ];
    }
}
