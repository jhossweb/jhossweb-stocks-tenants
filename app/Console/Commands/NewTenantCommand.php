<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Tenant\CreateTenant;
use App\DTOs\Tenant\RegisterTenantData;
use App\Models\Tenant;
use Illuminate\Console\Command;

class NewTenantCommand extends Command
{
    protected $signature = 'tenant:new
        {--name= : Nombre de la empresa}
        {--slug= : Slug/ID del tenant (se genera del nombre si se omite)}
        {--admin-name= : Nombre del usuario admin}
        {--admin-email= : Email del usuario admin}
        {--admin-password= : Contraseña del admin (mín. 8)}
        {--plan=standard : Plan del tenant}
        {--status=active : Estado inicial (active|suspended)}';

    protected $description = 'Crea un tenant con su dominio y lo aprovisiona (empresa + admin)';

    public function handle(CreateTenant $createTenant): int
    {
        $name = $this->option('name') ?: $this->ask('Nombre de la empresa');
        $adminName = $this->option('admin-name') ?: $this->ask('Nombre del usuario admin');
        $adminEmail = $this->option('admin-email') ?: $this->ask('Email del usuario admin');
        $adminPassword = $this->option('admin-password') ?: $this->secret('Contraseña del admin');

        if ($name === null || $adminEmail === null || $adminPassword === null) {
            $this->error('Nombre, email y contraseña son obligatorios.');

            return self::FAILURE;
        }

        if (mb_strlen($adminPassword) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');

            return self::FAILURE;
        }

        $status = $this->option('status');
        if (! in_array($status, [Tenant::STATUS_ACTIVE, Tenant::STATUS_SUSPENDED], true)) {
            $this->error('El estado debe ser active o suspended.');

            return self::FAILURE;
        }

        $tenant = $createTenant->handle(new RegisterTenantData(
            companyName: $name,
            adminName: (string) $adminName,
            adminEmail: $adminEmail,
            adminPassword: $adminPassword,
            slug: $this->option('slug') ?: null,
            plan: $this->option('plan'),
            status: $status,
        ));

        $this->info("Tenant [{$tenant->id}] creado y aprovisionado.");
        $this->info("Dominio: http://{$tenant->domain()}/login");

        return self::SUCCESS;
    }
}
