<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\User;
use App\Services\Tenancy\TenantPlanResolver;
use Database\Seeders\Central\CentralAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithoutTenantProvisioning;

uses(RefreshDatabase::class, WithoutTenantProvisioning::class);

beforeEach(function (): void {
    $this->withoutTenantProvisioning();
    $this->seed(CentralAccessSeeder::class);
});

function planAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

    return $user;
}

function planPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'pro',
        'name' => 'Pro',
        'description' => null,
        'billing_cycle' => 'monthly',
        'price_usd' => '49.00',
        'trial_days' => 0,
        'is_active' => true,
        'limits' => [],
        'module_keys' => ['inventory', 'sales', 'purchases', 'finance', 'expenses', 'payroll'],
        'features' => ['sales.quotes'],
    ], $overrides);
}

it('treats a null feature list as fully enabled for backward compatibility', function (): void {
    $plan = Plan::factory()->create(['features' => null]);

    expect($plan->hasFeature('sales.quotes'))->toBeTrue();

    $plan->features = ['sales.quotes'];
    expect($plan->hasFeature('sales.quotes'))->toBeTrue();

    $plan->features = [];
    expect($plan->hasFeature('sales.quotes'))->toBeFalse();
});

it('stores enabled features when creating a plan from the admin panel', function (): void {
    $this->actingAs(planAdmin())
        ->post(route('admin.plans.store'), planPayload(['features' => ['sales.quotes']]))
        ->assertRedirect(route('admin.plans.index'));

    $plan = Plan::where('code', 'pro')->firstOrFail();

    expect($plan->features)->toBe(['sales.quotes']);
    expect($plan->hasFeature('sales.quotes'))->toBeTrue();
});

it('stores no features when the plan disables them', function (): void {
    $this->actingAs(planAdmin())
        ->post(route('admin.plans.store'), planPayload(['features' => []]))
        ->assertRedirect(route('admin.plans.index'));

    $plan = Plan::where('code', 'pro')->firstOrFail();

    expect($plan->features)->toBe([]);
    expect($plan->hasFeature('sales.quotes'))->toBeFalse();
});

it('updates features when editing a plan from the admin panel', function (): void {
    $plan = Plan::factory()->create([
        'code' => 'pro',
        'features' => ['sales.quotes'],
    ]);

    $this->actingAs(planAdmin())
        ->put(route('admin.plans.update', $plan), planPayload(['features' => []]))
        ->assertRedirect(route('admin.plans.index'));

    expect($plan->refresh()->hasFeature('sales.quotes'))->toBeFalse();
});

it('blocks quote routes when the active plan disables the feature', function (): void {
    $this->mock(TenantPlanResolver::class, function ($mock): void {
        $mock->shouldReceive('allows')->andReturn(false);
    });

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/quotes')
        ->assertForbidden();
});
