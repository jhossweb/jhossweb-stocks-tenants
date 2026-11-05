<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\DTOs\Settings\TenantUserData;
use App\Exceptions\UsageLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreTenantUserRequest;
use App\Http\Requests\Settings\UpdateTenantUserRequest;
use App\Models\User;
use App\Services\Settings\TenantUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TenantUserController extends Controller
{
    public function __construct(
        private readonly TenantUserService $service,
    ) {}

    public function index(Request $request): Response
    {
        $users = $this->service->paginate((int) $request->get('per_page', 15));

        return Inertia::render('settings/Users', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'used' => $this->service->used(),
            'limit' => $this->service->limit(),
        ]);
    }

    public function store(StoreTenantUserRequest $request): RedirectResponse
    {
        $this->service->create(TenantUserData::fromArray($request->validated()));

        return back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(UpdateTenantUserRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, TenantUserData::fromArray($request->validated()));

        return back()->with('success', 'Usuario actualizado exitosamente.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        try {
            $this->service->deactivate($user, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Usuario inactivado exitosamente.');
    }

    public function activate(User $user): RedirectResponse
    {
        try {
            $this->service->activate($user);
        } catch (\DomainException|UsageLimitExceededException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Usuario reactivado exitosamente.');
    }
}
