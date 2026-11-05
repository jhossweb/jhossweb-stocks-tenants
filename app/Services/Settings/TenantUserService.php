<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\DTOs\Settings\TenantUserData;
use App\Enum\Tenancy\TenantLimit;
use App\Models\User;
use App\Services\Tenancy\UsageLimiter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class TenantUserService
{
    public function __construct(
        private readonly UsageLimiter $usageLimiter,
        private readonly TenantAuditLogger $audit,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(TenantUserData $data): User
    {
        $this->usageLimiter->assertCanCreate(TenantLimit::UsersMaxTotal);

        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password ?? str()->random(32),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->syncRoles($data->roles);

        $this->audit->log('user.created', $user, [], [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $data->roles,
        ]);

        return $user;
    }

    public function update(User $user, TenantUserData $data): User
    {
        $user->name = $data->name;
        $user->email = $data->email;

        if ($data->password !== null) {
            $user->password = Hash::make($data->password);
        }

        $old = ['name' => $user->getOriginal('name'), 'email' => $user->getOriginal('email')];

        $user->save();
        $user->syncRoles($data->roles);

        $this->audit->log('user.updated', $user, $old, [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $data->roles,
        ]);

        return $user->refresh();
    }

    public function deactivate(User $user, User $actor): User
    {
        if ($user->is($actor)) {
            throw new \DomainException('No puedes inactivar tu propio usuario.');
        }

        if (! $user->isActive()) {
            return $user;
        }

        if ($user->hasRole('admin') && $this->activeAdminsCount() <= 1) {
            throw new \DomainException('No puedes inactivar al último administrador activo.');
        }

        $user->is_active = false;
        $user->deactivated_at = now();
        $user->deactivated_by = $actor->id;
        $user->save();

        $this->audit->log('user.deactivated', $user, [], [
            'is_active' => false,
        ]);

        return $user->refresh();
    }

    public function activate(User $user): User
    {
        if ($user->isActive()) {
            return $user;
        }

        $this->usageLimiter->assertCanCreate(TenantLimit::UsersMaxTotal);

        $user->is_active = true;
        $user->deactivated_at = null;
        $user->deactivated_by = null;
        $user->save();

        $this->audit->log('user.activated', $user, [], [
            'is_active' => true,
        ]);

        return $user->refresh();
    }

    public function used(): int
    {
        return User::query()->where('is_active', true)->count();
    }

    public function limit(): ?int
    {
        return $this->usageLimiter->getLimit(TenantLimit::UsersMaxTotal);
    }

    private function activeAdminsCount(): int
    {
        return User::query()
            ->where('is_active', true)
            ->role('admin')
            ->count();
    }
}
