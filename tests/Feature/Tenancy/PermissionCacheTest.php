<?php

declare(strict_types=1);

use App\Enum\Tenancy\TenantPermission;
use App\Models\User;
use App\Services\Settings\TenantPermissionService;
use App\Services\Settings\UserAccessNames;
use App\Tenancy\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(TenantPermissionService::class)->sync();
});

it('conserva la caché de permisos al resetear el estado en memoria', function (): void {
    $registrar = app(Spatie\Permission\PermissionRegistrar::class);

    expect($registrar)->toBeInstanceOf(PermissionRegistrar::class);

    Cache::forget($registrar->cacheKey);
    $registrar->resetInMemoryState();
    $registrar->getPermissions();

    expect(Cache::has($registrar->cacheKey))->toBeTrue();

    $registrar->resetInMemoryState();

    expect(Cache::has($registrar->cacheKey))->toBeTrue();
});

it('obtiene solo los nombres de roles y permisos del usuario', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('admin');

    $access = app(UserAccessNames::class)->for($user);

    expect($access['roles'])->toBe(['admin'])
        ->and($access['permissions'])->toHaveCount(count(TenantPermission::keys()));
});
