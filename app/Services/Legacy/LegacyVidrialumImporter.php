<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Tenant;
use App\Models\TransactionType;
use App\Models\User;
use Database\Seeders\TransactionTypeSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Copia los datos del ERP legacy (schema `legacy`, con `users` en `public`)
 * hacia el schema de un tenant.
 *
 * Uso: una sola vez por tenant. Las columnas son idénticas (verificado),
 * así que se copian con INSERT ... SELECT de columnas explícitas en orden de
 * dependencias de FOREIGN KEY (padres antes que hijos) para no deshabilitar
 * triggers ni constraints.
 */
class LegacyVidrialumImporter
{
    private const EXCLUDED_TABLES = ['transaction_types'];

    /**
     * Schema donde se archivan las tablas de negocio del ERP legacy
     * (resultado de la limpieza de `public`, Fase 6 del plan SaaS).
     */
    private const LEGACY_SCHEMA = 'legacy';

    /**
     * Tablas que NO se movieron al schema `legacy` y siguen en `public`.
     */
    private const KEEP_IN_PUBLIC = ['users'];

    /**
     * Tablas de negocio en orden topológico (padres primero).
     */
    private const TABLE_ORDER = [
        'company',
        'brands',
        'categories',
        'warehouses',
        'suppliers',
        'customers',
        'rates',
        'expense_categories',
        'bank_accounts',
        'adjustment_reasons',
        'users',
        'employees',
        'payroll_periods',
        'products',
        'purchase_orders',
        'sale_orders',
        'accounts_payables',
        'accounts_receivable',
        'bank_transactions',
        'cash_closings',
        'cash_movements',
        'components_productos',
        'cost_histories',
        'price_histories',
        'inventories',
        'movements_purchases',
        'movements_sales',
        'movements_inventories',
        'payrolls',
        'payroll_deductions',
        'expenses',
        'account_payments',
        'receipts',
        'stocks',
    ];

    public function __construct(private readonly Tenant $tenant) {}

    /**
     * @return array<string, array{legacy:int, tenant:int}>
     */
    public function import(): array
    {
        $this->assertIdempotent();

        return $this->tenant->run(function (): array {
            $this->seedTransactionTypes();

            foreach (self::TABLE_ORDER as $table) {
                $this->copyTable($table);
            }

            $this->assignAdminRoleToUsers();

            foreach (array_diff(self::TABLE_ORDER, self::EXCLUDED_TABLES) as $table) {
                $this->resyncSequence($table);
            }

            $this->syncTransactionLastNumbers();

            Cache::flush();

            return $this->verify();
        });
    }

    public function syncFolios(): void
    {
        $this->tenant->run(function (): void {
            $this->syncTransactionLastNumbers();
        });
    }

    private function assertIdempotent(): void
    {
        $this->tenant->run(function (): void {
            $guardTables = ['products', 'suppliers', 'customers'];

            foreach ($guardTables as $table) {
                if (DB::table($table)->exists()) {
                    throw new RuntimeException(
                        "El tenant {$this->tenant->id} ya contiene datos en `{$table}`. Abortando importación."
                    );
                }
            }
        });
    }

    /**
     * Schema de origen de una tabla legacy: `legacy` salvo las que se
     * conservan en `public` (ej: `users`, auth del portal central).
     */
    private function sourceSchema(string $table): string
    {
        return in_array($table, self::KEEP_IN_PUBLIC, true)
            ? 'public'
            : self::LEGACY_SCHEMA;
    }

    /**
     * @return list<string>
     */
    private function columnsOf(string $table): array
    {
        $rows = DB::select(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position',
            [$this->sourceSchema($table), $table]
        );

        return array_map(fn ($row): string => $row->column_name, $rows);
    }

    private function copyTable(string $table): void
    {
        $columns = $this->columnsOf($table);

        if ($columns === []) {
            return;
        }

        // La company que crea el pipeline de provisioning es un placeholder
        // (cmp_rif SIN-RIF); la legacy trae los datos reales de la empresa.
        if ($table === 'company') {
            DB::table($table)->delete();
        }

        $quoted = implode(', ', array_map(fn (string $col): string => '"'.$col.'"', $columns));

        $schema = $this->sourceSchema($table);

        DB::statement(
            "INSERT INTO \"{$table}\" ({$quoted}) SELECT {$quoted} FROM \"{$schema}\".\"{$table}\""
        );
    }

    private function seedTransactionTypes(): void
    {
        (new TransactionTypeSeeder)->run();
    }

    private function assignAdminRoleToUsers(): void
    {
        $role = Role::findOrCreate('admin', 'web');

        foreach (User::query()->get() as $user) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }

    private function resyncSequence(string $table): void
    {
        DB::statement(
            "SELECT setval(pg_get_serial_sequence(?, 'id'), GREATEST(COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1), 1))",
            [$table]
        );
    }

    /**
     * Alinea el foliador de cada tipo de transacción con el máximo número
     * legacy usado, para no volver a emitir folios existentes.
     */
    private function syncTransactionLastNumbers(): void
    {
        $prefixes = [
            'FAC' => ['fac'],
            'FACV' => ['fav', 'facv'],
            'DEVC' => ['dev'],
            'DEVV' => ['devv'],
            'INV' => ['inv'],
            'COT' => ['cot'],
        ];

        foreach ($prefixes as $code => $prefixList) {
            $max = $this->maxLegacyNumber($prefixList);
            $next = $max > 0 ? $max + 1 : null;

            if ($next === null) {
                continue;
            }

            TransactionType::query()
                ->where('trans_type_code', $code)
                ->update(['trans_last_number' => $next]);
        }
    }

    /**
     * @param  list<string>  $prefixes
     */
    private function maxLegacyNumber(array $prefixes): int
    {
        $pattern = '^('.implode('|', array_map(fn (string $p): string => preg_quote($p, '/'), $prefixes)).')';
        $numbers = collect();

        foreach (['sale_orders' => 'sls_number', 'purchase_orders' => 'invoice_number'] as $table => $column) {
            $rows = DB::select("SELECT \"{$column}\" FROM \"".self::LEGACY_SCHEMA."\".\"{$table}\" WHERE \"{$column}\" ~* ?", [$pattern]);

            foreach ($rows as $row) {
                if (preg_match_all('/\d+/', (string) $row->{$column}, $matches) > 0) {
                    $numbers->push((int) end($matches[0]));
                }
            }
        }

        return $numbers->max() ?? 0;
    }

    /**
     * @return array<string, array{legacy:int, tenant:int}>
     */
    private function verify(): array
    {
        $report = [];

        foreach (array_diff(self::TABLE_ORDER, self::EXCLUDED_TABLES) as $table) {
            $report[$table] = [
                'legacy' => (int) DB::selectOne('SELECT count(*) AS c FROM "'.$this->sourceSchema($table)."\".\"{$table}\"")->c,
                'tenant' => (int) DB::table($table)->count(),
            ];
        }

        return $report;
    }
}
