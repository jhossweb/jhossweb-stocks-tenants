<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Guard;

class UserAccessNames
{
    /**
     * @var array<string, array{roles: list<string>, permissions: list<string>}>
     */
    private array $cache = [];

    /**
     * Devuelve solo los nombres de roles y permisos del usuario, sin hidratar
     * modelos de Spatie. Evita cargar cientos de Role/Permission por request.
     *
     * @return array{roles: list<string>, permissions: list<string>}
     */
    public function for(User $user): array
    {
        $cacheKey = (string) $user->getKey();

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $morphKey = $columns['model_morph_key'] ?? 'model_id';
        $rolePivot = $columns['role_pivot_key'] ?? 'role_id';
        $permissionPivot = $columns['permission_pivot_key'] ?? 'permission_id';
        $guard = Guard::getDefaultName($user);
        $morphClass = $user->getMorphClass();

        $roles = DB::table($tables['roles'].' as roles')
            ->join($tables['model_has_roles'].' as model_roles', 'roles.id', '=', 'model_roles.'.$rolePivot)
            ->where('model_roles.'.$morphKey, $user->getKey())
            ->where('model_roles.model_type', $morphClass)
            ->where('roles.guard_name', $guard)
            ->pluck('roles.name');

        $viaRoles = DB::table($tables['permissions'].' as permissions')
            ->join($tables['role_has_permissions'].' as role_permissions', 'permissions.id', '=', 'role_permissions.'.$permissionPivot)
            ->join($tables['model_has_roles'].' as model_roles', 'role_permissions.'.$rolePivot, '=', 'model_roles.'.$rolePivot)
            ->where('model_roles.'.$morphKey, $user->getKey())
            ->where('model_roles.model_type', $morphClass)
            ->where('permissions.guard_name', $guard)
            ->pluck('permissions.name');

        $direct = DB::table($tables['permissions'].' as permissions')
            ->join($tables['model_has_permissions'].' as model_permissions', 'permissions.id', '=', 'model_permissions.'.$permissionPivot)
            ->where('model_permissions.'.$morphKey, $user->getKey())
            ->where('model_permissions.model_type', $morphClass)
            ->where('permissions.guard_name', $guard)
            ->pluck('permissions.name');

        return $this->cache[$cacheKey] = [
            'roles' => $roles->unique()->values()->all(),
            'permissions' => $viaRoles->merge($direct)->unique()->values()->all(),
        ];
    }
}
