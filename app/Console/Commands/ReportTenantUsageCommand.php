<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReportTenantUsageCommand extends Command
{
    protected $signature = 'saas:tenants:usage-report';

    protected $description = 'Genera reporte de uso de recursos por tenant';

    public function handle(): int
    {
        $this->info('Reporte de uso (placeholder F5)');

        return self::SUCCESS;
    }
}
