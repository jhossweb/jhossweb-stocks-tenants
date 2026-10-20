<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Tenancy;

use App\DTOs\Tenancy\SubscriptionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenancy\Subscription\AssignPlanRequest;
use App\Http\Requests\Admin\Tenancy\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Admin\Tenancy\Subscription\UpdateSubscriptionRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Repositories\Tenancy\PlanRepository;
use App\Repositories\Tenancy\SubscriptionRepository;
use App\Repositories\Tenancy\TenantRepository;
use App\Services\Tenancy\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionRepository $repository,
        private readonly SubscriptionService $service,
        private readonly TenantRepository $tenantRepository,
        private readonly PlanRepository $planRepository,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'plan_id' => $request->get('plan_id'),
            'tenant_id' => $request->get('tenant_id'),
        ];

        $subscriptions = $this->repository->paginate(
            $filters,
            (int) $request->get('per_page', 15),
            $request->get('sort'),
            (string) $request->get('direction', 'desc'),
        )->through(fn (Subscription $subscription): array => $this->present($subscription));

        return Inertia::render('admin/subscriptions/index', [
            'subscriptions' => $subscriptions,
            'filters' => $filters,
            'statuses' => ['trialing', 'active', 'past_due', 'suspended', 'canceled', 'expired'],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/subscriptions/create', [
            'tenants' => $this->tenantOptions(),
            'plans' => $this->planOptions(),
            'selectedTenantId' => $request->get('tenant_id'),
        ]);
    }

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $subscription = $this->service->create(SubscriptionData::fromArray($request->validated()));

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', "Suscripción #{$subscription->id} creada.");
    }

    public function edit(Subscription $subscription): Response
    {
        return Inertia::render('admin/subscriptions/edit', [
            'subscription' => $this->present($subscription->load(['tenant', 'plan'])),
            'tenants' => $this->tenantOptions(),
            'plans' => $this->planOptions(),
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $this->service->update($subscription, SubscriptionData::fromArray($request->validated()));

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', "Suscripción #{$subscription->id} actualizada.");
    }

    public function renew(Subscription $subscription): RedirectResponse
    {
        $this->service->renew($subscription);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', "Suscripción #{$subscription->id} renovada.");
    }

    public function assignPlan(AssignPlanRequest $request, Tenant $tenant): RedirectResponse
    {
        $plan = Plan::query()->findOrFail($request->validated('plan_id'));
        $this->service->assign($tenant, $plan);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', "Plan {$plan->name} asignado a {$tenant->name}.");
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $id = $subscription->id;
        $this->service->delete($subscription);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', "Suscripción #{$id} eliminada.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tenantOptions(): array
    {
        return $this->tenantRepository->all()
            ->map(fn ($tenant): array => ['id' => $tenant->id, 'name' => $tenant->name])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function planOptions(): array
    {
        return $this->planRepository->all(['is_active' => true])
            ->map(fn ($plan): array => [
                'id' => $plan->id,
                'code' => $plan->code,
                'name' => $plan->name,
                'billing_cycle' => $plan->billing_cycle,
                'price_usd' => $plan->price_usd,
                'trial_days' => $plan->trial_days,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'tenant_name' => $subscription->tenant?->name,
            'plan_id' => $subscription->plan_id,
            'plan_name' => $subscription->plan?->name,
            'status' => $subscription->status,
            'started_at' => $subscription->started_at?->toDateString(),
            'trial_ends_at' => $subscription->trial_ends_at?->toDateString(),
            'current_period_start' => $subscription->current_period_start?->toDateString(),
            'current_period_end' => $subscription->current_period_end?->toDateString(),
            'canceled_at' => $subscription->canceled_at?->toDateString(),
            'grace_days' => $subscription->grace_days,
            'created_at' => $subscription->created_at?->toDateTimeString(),
        ];
    }
}
