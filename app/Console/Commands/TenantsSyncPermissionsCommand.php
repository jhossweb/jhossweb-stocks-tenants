<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Settings\TenantPermissionService;
use Illuminate\Console\Command;

class TenantsSyncPermissionsCommand extends Command
{
    protected $signature = 'tenants:sync-permissions {--tenant= : Solo sincroniza este tenant}';

    protected $description = 'Sincroniza el catálogo fijo de permisos y los roles por defecto en los tenants';

    public function handle(): int
    {
        $query = Tenant::query();

        if ($tenantId = $this->option('tenant')) {
            $query->whereKey($tenantId);
        }

        $count = 0;

        $query->each(function (Tenant $tenant) use (&$count): void {
            try {
                $tenant->run(function (): void {
                    app(TenantPermissionService::class)->sync();
                });

                $count++;
                $this->line("OK: {$tenant->id}");
            } catch (\Throwable $e) {
                $this->error("Error en {$tenant->id}: {$e->getMessage()}");
            }
        });

        $this->info("Tenants sincronizados: {$count}");

        return self::SUCCESS;
    }
}
