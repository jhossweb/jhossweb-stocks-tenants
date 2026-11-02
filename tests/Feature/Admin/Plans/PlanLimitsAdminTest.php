<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantLimitOverride;
use App\Models\User;
use Database\Seeders\Central\CentralAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithoutTenantProvisioning;

uses(RefreshDatabase::class, WithoutTenantProvisioning::class);

beforeEach(function (): void {
    $this->withoutTenantProvisioning();
    $this->seed(CentralAccessSeeder::class);
});

function limitsAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

    return $user;
}

function planLimitsPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'pro',
        'name' => 'Pro',
        'description' => null,
        'billing_cycle' => 'monthly',
        'price_usd' => '49.00',
        'trial_days' => 0,
        'is_active' => true,
        'module_keys' => ['inventory', 'sales', 'purchases', 'finance', 'expenses', 'payroll'],
        'features' => ['sales.quotes'],
        'limits' => [
            'sales.invoices_monthly' => 100,
            'sales.quotes_monthly' => 50,
            'customers.max_total' => 10,
            'warehouses.max_total' => 2,
        ],
    ], $overrides);
}

it('stores the new limits when creating a plan', function (): void {
    $this->actingAs(limitsAdmin())
        ->postJson(route('admin.plans.store'), planLimitsPayload())
        ->assertRedirect(route('admin.plans.index'));

    $plan = Plan::where('code', 'pro')->firstOrFail();

    expect($plan->limits)->toMatchArray([
        'sales.invoices_monthly' => 100,
        'sales.quotes_monthly' => 50,
        'customers.max_total' => 10,
        'warehouses.max_total' => 2,
    ]);
});

it('updates limits, removes null values and preserves unknown keys', function (): void {
    $plan = Plan::factory()->create([
        'code' => 'pro',
        'limits' => [
            'legacy.key' => 7,
            'sales.quotes_monthly' => 50,
            'products.max_total' => 10,
        ],
    ]);

    $this->actingAs(limitsAdmin())
        ->putJson(route('admin.plans.update', $plan), planLimitsPayload([
            'limits' => [
                'sales.quotes_monthly' => null,
                'products.max_total' => 3,
            ],
        ]))
        ->assertRedirect(route('admin.plans.index'));

    $limits = $plan->refresh()->limits;

    expect($limits)->toMatchArray([
        'legacy.key' => 7,
        'products.max_total' => 3,
    ])->not->toHaveKey('sales.quotes_monthly');
});

it('rejects unknown limit keys and negative values', function (): void {
    $this->actingAs(limitsAdmin())
        ->postJson(route('admin.plans.store'), planLimitsPayload([
            'limits' => ['unknown.key' => 5, 'products.max_total' => -1],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['limits']);
});

it('stores and removes tenant limit overrides', function (): void {
    $tenant = Tenant::factory()->create();
    $plan = Plan::factory()->create(['limits' => ['sales.quotes_monthly' => 10]]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $this->actingAs(limitsAdmin())
        ->putJson(route('admin.tenants.limit-overrides.update', $tenant), [
            'overrides' => ['sales.quotes_monthly' => 25],
        ])
        ->assertRedirect(route('admin.tenants.show', $tenant));

    expect(TenantLimitOverride::where('tenant_id', $tenant->id)->where('key', 'sales.quotes_monthly')->value('value'))
        ->toBe(25);

    $this->actingAs(limitsAdmin())
        ->putJson(route('admin.tenants.limit-overrides.update', $tenant), [
            'overrides' => ['sales.quotes_monthly' => null],
        ])
        ->assertRedirect(route('admin.tenants.show', $tenant));

    expect(TenantLimitOverride::where('tenant_id', $tenant->id)->where('key', 'sales.quotes_monthly')->exists())
        ->toBeFalse();
});
