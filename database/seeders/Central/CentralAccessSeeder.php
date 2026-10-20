<?php

declare(strict_types=1);

namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CentralAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permisos del panel central
        $permissions = [
            'admin.access',
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',
            'tenants.status',
            'plans.view',
            'plans.create',
            'plans.update',
            'plans.delete',
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.update',
            'subscriptions.delete',
            'payments.view',
            'payments.create',
            'payments.void',
            'rates.view',
            'rates.create',
            'rates.update',
            'rates.delete',
            'usage.view',
            'users.view',
            'users.update',
            'settings.view',
            'settings.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Roles del panel central
        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $superAdmin->syncPermissions($permissions);

        $suscripciones = Role::firstOrCreate([
            'name' => 'suscripciones',
            'guard_name' => 'web',
        ]);
        $suscripciones->syncPermissions([
            'admin.access',
            'tenants.view',
            'plans.view',
            'plans.create',
            'plans.update',
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.update',
            'payments.view',
            'payments.create',
            'payments.void',
            'rates.view',
            'rates.create',
            'rates.update',
            'usage.view',
        ]);

        $soporte = Role::firstOrCreate([
            'name' => 'soporte',
            'guard_name' => 'web',
        ]);
        $soporte->syncPermissions([
            'admin.access',
            'tenants.view',
            'tenants.status',
            'subscriptions.view',
            'payments.view',
            'usage.view',
        ]);

        $auditor = Role::firstOrCreate([
            'name' => 'auditor',
            'guard_name' => 'web',
        ]);
        $auditor->syncPermissions([
            'admin.access',
            'tenants.view',
            'subscriptions.view',
            'payments.view',
            'usage.view',
            'plans.view',
            'rates.view',
        ]);
    }
}
