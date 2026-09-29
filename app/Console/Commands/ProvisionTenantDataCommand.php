<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Tenant\ProvisionTenantData;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ProvisionTenantDataCommand extends Command
{
    protected $signature = 'tenants:provision
        {tenant : Tenant id (slug) a aprovisionar}
        {--name= : Nombre de la empresa (usa el nombre del tenant si se omite)}
        {--email= : Email del usuario admin}
        {--password= : Password del usuario admin}';

    protected $description = 'Crea company + usuario admin (verificado, rol admin) en el schema de un tenant';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));

        if ($tenant === null) {
            $this->error("Tenant [{$this->argument('tenant')}] no encontrado.");

            return self::FAILURE;
        }

        // El trait VirtualColumn reconstruye `data` desde los atributos, así que
        // los campos de provisioning se asignan como atributos (respeta el cast
        // 'hashed' de User: se guarda ya el hash y no se re-hashea).
        if ($this->option('name')) {
            $tenant->name = $this->option('name');
        }
        if ($this->option('email')) {
            $tenant->admin_email = $this->option('email');
        }
        if ($this->option('password')) {
            $tenant->admin_password = Hash::make($this->option('password'));
        }

        if (! $this->option('name') && ! $this->option('email') && ! $this->option('password')) {
            $this->warn('No se pasó ningún dato. Uso: php artisan tenants:provision {slug} --email= --password= [--name=].');

            return self::FAILURE;
        }

        $tenant->save();

        (new ProvisionTenantData($tenant))->handle();

        $this->info("Tenant [{$tenant->id}] aprovisionado correctamente.");

        return self::SUCCESS;
    }
}
