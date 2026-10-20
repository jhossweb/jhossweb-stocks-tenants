<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class GrantRoleCommand extends Command
{
    protected $signature = 'admin:grant-role {email} {role}';

    protected $description = 'Asignar rol a usuario del panel central';

    public function handle(): int
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("Usuario no encontrado: {$email}");

            return self::FAILURE;
        }

        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            $this->error("Rol no encontrado: {$roleName}");

            return self::FAILURE;
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $this->info("Rol {$roleName} asignado a {$user->email}");

        return self::SUCCESS;
    }
}
