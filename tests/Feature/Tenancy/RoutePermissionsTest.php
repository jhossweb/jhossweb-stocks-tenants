<?php

declare(strict_types=1);

use App\Enum\Tenancy\TenantPermission;
use App\Models\User;
use App\Services\Settings\TenantPermissionService;
use App\Tenancy\RoutePermissionMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('toda ruta de tenant tiene un permiso del catálogo o está en la allowlist', function (): void {
    $missing = [];

    foreach (Route::getRoutes() as $route) {
        $middleware = $route->gatherMiddleware();

        if (! collect($middleware)->contains(fn ($item) => str_contains((string) $item, 'InitializeTenancy') || $item === 'tenant')) {
            continue;
        }

        $name = $route->getName();

        if ($name !== null && in_array($name, RoutePermissionMap::ALLOWLIST, true)) {
            continue;
        }

        $permission = RoutePermissionMap::permissionForRoute($route);

        if ($permission === null || ! TenantPermission::exists($permission)) {
            $missing[] = ($name ?? 'UNNAMED').' | '.$route->uri();
        }
    }

    expect($missing)->toBe([]);
});

it('bloquea una ruta del tenant si el usuario no tiene el permiso', function (): void {
    config(['tenancy.enforce_permissions_in_tests' => true]);

    app(TenantPermissionService::class)->sync();

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/products')
        ->assertForbidden();

    $user->givePermissionTo('inventory.products.view');

    $this->actingAs($user)
        ->get('/products')
        ->assertOk();
});
