<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenancy\TenantService;
use Illuminate\Console\Command;

class BackfillTenantDomainsCommand extends Command
{
    protected $signature = 'tenancy:backfill-domains';

    protected $description = 'Crea el subdominio de los tenants que no tienen uno (backfill idempotente)';

    public function handle(TenantService $service): int
    {
        $tenants = Tenant::query()
            ->whereDoesntHave('domains')
            ->get();

        $count = 0;

        foreach ($tenants as $tenant) {
            $service->ensureDomain($tenant);
            $count++;
        }

        $this->info("Subdominios creados para {$count} tenant(s).");

        return self::SUCCESS;
    }
}
