<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\DTOs\Settings\TenantRoleData;
use App\Enum\Tenancy\TenantPermission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleService
{
    public function __construct(
        private readonly TenantAuditLogger $audit,
    ) {}

    public function paginate(int $perPage = 50): LengthAwarePaginator
    {
        return Role::query()
            ->withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(TenantRoleData $data): Role
    {
        $role = Role::create([
            'name' => $data->name,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->validPermissions($data->permissions));
        $this->forgetCache();

        $this->audit->log('role.created', $role, [], [
            'name' => $role->name,
            'permissions' => $data->permissions,
        ]);

        return $role->refresh();
    }

    public function update(Role $role, TenantRoleData $data): Role
    {
        if ($role->name === 'admin') {
            throw new \DomainException('El rol admin está protegido y no puede modificarse.');
        }

        $role->name = $data->name;
        $role->save();
        $role->syncPermissions($this->validPermissions($data->permissions));
        $this->forgetCache();

        $this->audit->log('role.updated', $role, [], [
            'name' => $role->name,
            'permissions' => $data->permissions,
        ]);

        return $role->refresh();
    }

    public function delete(Role $role): void
    {
        if ($role->name === 'admin') {
            throw new \DomainException('El rol admin está protegido y no puede eliminarse.');
        }

        if ($role->users()->exists()) {
            throw new \DomainException('No puedes eliminar un rol asignado a usuarios. Reasigna esos usuarios primero.');
        }

        $name = $role->name;
        $role->delete();
        $this->forgetCache();

        $this->audit->log('role.deleted', null, [], ['name' => $name]);
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function validPermissions(array $permissions): array
    {
        $allowed = TenantPermission::keys();

        return array_values(array_intersect($permissions, $allowed));
    }

    private function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
