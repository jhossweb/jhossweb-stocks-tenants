<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrationReportCommand extends Command
{
    protected $signature = 'saas:tenants:migration-report';

    protected $description = 'Lista tenants con esquema desfasado respecto a migraciones tenant';

    public function handle(): int
    {
        $this->info('Reporte de migraciones por tenant (placeholder F5)');

        return self::SUCCESS;
    }
}
