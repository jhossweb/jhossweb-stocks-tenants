<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Tenancy;

use App\DTOs\Tenancy\PlanData;
use App\Enum\Tenancy\TenantFeature;
use App\Enum\Tenancy\TenantLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenancy\Plan\StorePlanRequest;
use App\Http\Requests\Admin\Tenancy\Plan\UpdatePlanRequest;
use App\Models\Plan;
use App\Repositories\Tenancy\PlanRepository;
use App\Services\Tenancy\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(
        private readonly PlanRepository $repository,
        private readonly PlanService $service,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->get('search'),
            'is_active' => $request->get('is_active'),
        ];
        $plans = $this->repository->paginate($filters, (int) $request->get('per_page', 15), $request->get('sort'), (string) $request->get('direction', 'desc'));

        return Inertia::render('admin/plans/index', [
            'plans' => $plans,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/plans/create', [
            'feature_groups' => TenantFeature::grouped(),
            'limit_groups' => TenantLimit::grouped(),
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $this->service->create(PlanData::fromArray($request->validated()));

        return redirect()->route('admin.plans.index')->with('success', 'Plan creado');
    }

    public function show(Plan $plan): Response
    {
        return Inertia::render('admin/plans/Show', [
            'plan' => $plan->toArray(),
            'module_keys' => $plan->moduleKeys(),
            'feature_groups' => TenantFeature::grouped(),
            'limit_groups' => TenantLimit::grouped(),
        ]);
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('admin/plans/edit', [
            'plan' => $plan->toArray(),
            'module_keys' => $plan->moduleKeys(),
            'feature_groups' => TenantFeature::grouped(),
            'limit_groups' => TenantLimit::grouped(),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $this->service->update($plan, PlanData::fromArray($request->validated()));

        return redirect()->route('admin.plans.index')->with('success', 'Plan actualizado');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $this->service->delete($plan);

        return redirect()->route('admin.plans.index')->with('success', 'Plan eliminado');
    }
}
