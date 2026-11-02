<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Tenancy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenant\StoreTenantRequest;
use App\Http\Requests\Admin\Tenant\UpdateTenantRequest;
use App\Http\Requests\Admin\Tenant\UpdateTenantStatusRequest;
use App\Models\Tenant;
use App\Repositories\Tenancy\PlanRepository;
use App\Repositories\Tenancy\TenantRepository;
use App\Services\Tenancy\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantRepository $repository,
        private readonly TenantService $service,
        private readonly PlanRepository $plans,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        $tenants = $this->repository->paginate(
            filters: $filters,
            perPage: (int) $request->get('per_page', 15),
            sort: $request->get('sort'),
            direction: (string) $request->get('direction', 'desc'),
        );

        $presented = $tenants->through(fn (Tenant $tenant): array => $this->present($tenant));

        return Inertia::render('admin/tenants/Index', [
            'tenants' => $presented,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tenants/create', [
            'domainSuffix' => config('tenancy.tenant_domain_suffix'),
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenant = $this->service->create(array_merge(
            $request->validated(),
            ['id' => $request->validated('id') ?? $request->validated('slug') ?? null]
        ));

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant [{$tenant->id}] creado. Subdominio: {$tenant->domain()}");
    }

    public function show(Tenant $tenant): Response
    {
        return Inertia::render('admin/tenants/Show', [
            'tenant' => $this->present($tenant),
            'plans' => $this->plans->all(['is_active' => true])
                ->map(fn ($plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'billing_cycle' => $plan->billing_cycle,
                    'price_usd' => $plan->price_usd,
                ])
                ->all(),
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('admin/tenants/edit', [
            'tenant' => $this->present($tenant),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->service->update($tenant, $request->validated());

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', "Tenant [{$tenant->id}] actualizado.");
    }

    public function updateStatus(UpdateTenantStatusRequest $request, Tenant $tenant): RedirectResponse
    {
        $status = $request->validated('status');
        $reason = $request->validated('suspended_reason') ?? null;
        $userId = $request->user()?->id;

        if ($status === 'suspended') {
            $this->service->suspend($tenant, $reason, $userId);
        } elseif ($status === 'active') {
            $this->service->activate($tenant);
        } else {
            $this->service->changeStatus($tenant, (string) $status, $reason, $userId);
        }

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Estado del tenant actualizado.');
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
        $tenant->loadMissing('subscriptions.plan');
        $activeSubscription = $tenant->activeSubscription();
        $planName = $activeSubscription?->plan?->name ?? $tenant->plan;

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'plan' => $planName,
            'status' => $tenant->status,
            'domain' => $tenant->domain(),
            'admin_email' => $tenant->admin_email,
            'admin_name' => $tenant->admin_name,
            'rif' => $tenant->rif,
            'address' => $tenant->address,
            'phone' => $tenant->phone,
            'trial_ends_at' => $tenant->trial_ends_at?->toDateTimeString(),
            'internal_notes' => $tenant->internal_notes,
            'status_source' => $tenant->status_source,
            'suspended_reason' => $tenant->suspended_reason,
            'suspended_at' => $tenant->suspended_at?->toDateTimeString(),
            'suspended_by' => $tenant->suspended_by,
            'schema_version' => $tenant->schema_version,
            'created_at' => $tenant->created_at?->toDateTimeString(),
            'updated_at' => $tenant->updated_at?->toDateTimeString(),
        ];
    }
}
