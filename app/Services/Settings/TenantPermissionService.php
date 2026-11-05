<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Enum\Tenancy\TenantPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class TenantPermissionService
{
    public function __construct(
        private readonly TenantAuditLogger $audit,
    ) {}

    /**
     * Sincroniza el catálogo fijo en el tenant actual y asegura el rol admin
     * con todos los permisos. Los roles por defecto solo se siembran al
     * crearse; si ya existen se respeta su personalización.
     */
    public function sync(): void
    {
        $keys = TenantPermission::keys();

        foreach ($keys as $key) {
            Permission::findOrCreate($key, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($keys);

        foreach (DefaultTenantRoles::roles() as $name => $patterns) {
            if ($name === 'admin') {
                continue;
            }

            $role = Role::findOrCreate($name, 'web');

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions(TenantPermission::expand($patterns));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->audit->log('permissions.synced', null, [], [
            'permissions_count' => count($keys),
        ]);
    }
}
