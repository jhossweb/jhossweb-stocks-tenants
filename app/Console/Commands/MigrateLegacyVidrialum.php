<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Legacy\LegacyVidrialumImporter;
use Illuminate\Console\Command;

class MigrateLegacyVidrialum extends Command
{
    protected $signature = 'legacy:migrate {tenant? : ID/slug del tenant destino (default: vidrialum-ca)}
                          {--sync-folios : Solo realinea los foliadores del tenant ya importado}';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant') ?? 'vidrialum-ca';

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            $this->error("Tenant `{$tenantId}` no encontrado.");

            return self::FAILURE;
        }

        $importer = new LegacyVidrialumImporter($tenant);

        if ($this->option('sync-folios')) {
            $importer->syncFolios();
            $this->info('Foliadores realineados.');

            return self::SUCCESS;
        }

        $this->info("Migrando datos legacy a `{$tenantId}` ...");

        try {
            $report = $importer->import();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($report as $table => $counts) {
            $ok = $counts['legacy'] === $counts['tenant'] ? 'OK' : 'MISMATCH';

            $this->line(sprintf('%-24s legacy:%-4d tenant:%-4d %s', $table, $counts['legacy'], $counts['tenant'], $ok));
        }

        $mismatches = array_filter($report, fn (array $c): bool => $c['legacy'] !== $c['tenant']);

        if ($mismatches !== []) {
            $this->error('Importación completada pero con discrepancias.');

            return self::FAILURE;
        }

        $this->info('Migración completada y verificada.');

        return self::SUCCESS;
    }
}
