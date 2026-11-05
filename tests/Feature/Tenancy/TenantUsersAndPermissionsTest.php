<?php

declare(strict_types=1);

use App\DTOs\Settings\TenantUserData;
use App\Enum\Tenancy\TenantLimit;
use App\Enum\Tenancy\TenantPermission;
use App\Exceptions\UsageLimitExceededException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantLimitOverride;
use App\Models\User;
use App\Services\Settings\TenantPermissionService;
use App\Services\Settings\TenantUserService;
use App\Services\Tenancy\UsageLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithoutTenantProvisioning;

uses(RefreshDatabase::class, WithoutTenantProvisioning::class);

beforeEach(function (): void {
    $this->withoutTenantProvisioning();
    app(TenantPermissionService::class)->sync();
});

function bindTenantWithUserLimit(int $limit, array $overrides = []): Tenant
{
    $plan = Plan::factory()->create(['limits' => ['users.max_total' => $limit]]);
    $tenant = Tenant::factory()->create();

    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    foreach ($overrides as $key => $value) {
        TenantLimitOverride::create([
            'tenant_id' => $tenant->id,
            'key' => $key,
            'value' => $value,
        ]);
    }

    app()->instance('tenant', $tenant);

    return $tenant;
}

it('sincroniza el catálogo y el rol admin con todos los permisos', function (): void {
    expect(Permission::query()->where('name', 'sales.invoices.view')->exists())->toBeTrue();

    $admin = Role::findByName('admin', 'web');

    expect($admin->permissions()->count())->toBe(count(TenantPermission::keys()))
        ->and(Role::findByName('vendedor', 'web')->exists())->toBeTrue();
});

it('cuenta solo usuarios activos y bloquea al llegar al límite', function (): void {
    $tenant = bindTenantWithUserLimit(3);

    $actor = User::factory()->create();
    $actor->assignRole('admin');

    $service = app(TenantUserService::class);

    $userA = $service->create(TenantUserData::fromArray([
        'name' => 'A',
        'email' => 'a@example.com',
        'password' => 'password123',
        'roles' => ['vendedor'],
    ]));

    $service->create(TenantUserData::fromArray([
        'name' => 'B',
        'email' => 'b@example.com',
        'password' => 'password123',
        'roles' => ['vendedor'],
    ]));

    expect(app(UsageLimiter::class)->canCreate(TenantLimit::UsersMaxTotal))->toBeFalse();

    $service->deactivate($userA, $actor);

    expect(app(UsageLimiter::class)->canCreate(TenantLimit::UsersMaxTotal))->toBeTrue();
});

it('permite crear otro usuario al inactivar y bloquea reactivar si el límite está lleno', function (): void {
    $tenant = bindTenantWithUserLimit(3);

    $actor = User::factory()->create();
    $actor->assignRole('admin');

    $service = app(TenantUserService::class);

    $userA = $service->create(TenantUserData::fromArray([
        'name' => 'A', 'email' => 'a@example.com', 'password' => 'password123', 'roles' => ['vendedor'],
    ]));
    $userB = $service->create(TenantUserData::fromArray([
        'name' => 'B', 'email' => 'b@example.com', 'password' => 'password123', 'roles' => ['vendedor'],
    ]));

    $service->deactivate($userA, $actor);

    $service->create(TenantUserData::fromArray([
        'name' => 'C', 'email' => 'c@example.com', 'password' => 'password123', 'roles' => ['vendedor'],
    ]));

    expect(fn () => $service->activate($userA))->toThrow(UsageLimitExceededException::class);
});

it('aplica overrides de usuarios por tenant', function (): void {
    $tenant = bindTenantWithUserLimit(1, ['users.max_total' => 3]);

    expect(app(UsageLimiter::class)->getLimit(TenantLimit::UsersMaxTotal))->toBe(3);
});
