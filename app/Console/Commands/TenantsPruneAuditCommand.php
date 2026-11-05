<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantAuditLog;
use Illuminate\Console\Command;

class TenantsPruneAuditCommand extends Command
{
    protected $signature = 'tenants:prune-audit {--days=365 : Días de retención} {--tenant= : Solo este tenant}';

    protected $description = 'Elimina registros de auditoría más antiguos que la retención configurada';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $query = Tenant::query();

        if ($tenantId = $this->option('tenant')) {
            $query->whereKey($tenantId);
        }

        $deleted = 0;

        $query->each(function (Tenant $tenant) use ($cutoff, &$deleted): void {
            try {
                $tenant->run(function () use ($cutoff, &$deleted): void {
                    $deleted += TenantAuditLog::query()
                        ->where('created_at', '<', $cutoff)
                        ->delete();
                });
            } catch (\Throwable $e) {
                $this->error("Error en {$tenant->id}: {$e->getMessage()}");
            }
        });

        $this->info("Registros de auditoría eliminados: {$deleted}");

        return self::SUCCESS;
    }
}
