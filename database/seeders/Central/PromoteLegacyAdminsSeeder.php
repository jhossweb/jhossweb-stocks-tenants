<?php

declare(strict_types=1);

namespace Database\Seeders\Central;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PromoteLegacyAdminsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asigna rol super-admin a usuarios con is_admin = true (si existe columna)
        if (! \Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        $role = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        User::where('is_admin', true)
            ->whereDoesntHave('roles', function ($query) use ($role): void {
                $query->where('name', $role->name)
                    ->where('guard_name', $role->guard_name);
            })
            ->get()
            ->each(function (User $user) use ($role): void {
                $user->assignRole($role);
            });
    }
}
