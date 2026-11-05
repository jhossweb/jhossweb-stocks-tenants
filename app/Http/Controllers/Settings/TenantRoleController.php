<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\DTOs\Settings\TenantRoleData;
use App\Enum\Tenancy\TenantPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreTenantRoleRequest;
use App\Http\Requests\Settings\UpdateTenantRoleRequest;
use App\Services\Settings\TenantRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TenantRoleController extends Controller
{
    public function __construct(
        private readonly TenantRoleService $service,
    ) {}

    public function index(Request $request): Response
    {
        $roles = $this->service->paginate((int) $request->get('per_page', 50));

        return Inertia::render('settings/Roles', [
            'roles' => $roles,
            'permission_groups' => TenantPermission::grouped(),
        ]);
    }

    public function store(StoreTenantRoleRequest $request): RedirectResponse
    {
        $this->service->create(TenantRoleData::fromArray($request->validated()));

        return back()->with('success', 'Rol creado exitosamente.');
    }

    public function update(UpdateTenantRoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $this->service->update($role, TenantRoleData::fromArray($request->validated()));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        try {
            $this->service->delete($role);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Rol eliminado exitosamente.');
    }
}
