<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Enum\Tenancy\TenantPermission;

final class DefaultTenantRoles
{
    /**
     * @return array<string, list<string>>
     */
    public static function roles(): array
    {
        return [
            'admin' => ['*'],
            'gerente' => [
                'dashboard.view',
                'reports.*',
                'inventory.*',
                'sales.*',
                'purchases.*',
                'finance.*',
                'finance.transfers.*',
                'expenses.*',
                'payroll.*',
                'settings.company.*',
                'settings.rates.*',
                'settings.master_data.*',
            ],
            'vendedor' => [
                'dashboard.view',
                'reports.view',
                'reports.export',
                'sales.invoices.view',
                'sales.invoices.create',
                'sales.invoices.update',
                'sales.invoices.export',
                'sales.quotes.view',
                'sales.quotes.create',
                'sales.quotes.update',
                'sales.quotes.export',
                'sales.quotes.convert',
                'sales.returns.view',
                'sales.returns.create',
                'sales.returns.update',
                'sales.returns.export',
                'sales.customers.view',
                'sales.customers.create',
                'sales.customers.update',
                'sales.customers.export',
                'sales.receivables.view',
                'sales.receivables.export',
                'sales.receipts.view',
                'sales.receipts.create',
                'sales.receipts.update',
                'sales.receipts.export',
                'inventory.products.view',
                'inventory.kardex.view',
                'inventory.prices.view',
            ],
            'comprador' => [
                'dashboard.view',
                'reports.view',
                'reports.export',
                'purchases.invoices.view',
                'purchases.invoices.create',
                'purchases.invoices.update',
                'purchases.invoices.export',
                'purchases.returns.view',
                'purchases.returns.create',
                'purchases.returns.update',
                'purchases.returns.export',
                'purchases.suppliers.view',
                'purchases.suppliers.create',
                'purchases.suppliers.update',
                'purchases.suppliers.export',
                'purchases.payables.view',
                'purchases.payables.export',
                'purchases.payments.view',
                'purchases.payments.create',
                'purchases.payments.update',
                'purchases.payments.export',
                'inventory.products.view',
                'inventory.kardex.view',
            ],
            'almacenista' => [
                'dashboard.view',
                'reports.view',
                'reports.export',
                'inventory.*',
                'purchases.invoices.view',
                'sales.invoices.view',
            ],
            'contador' => [
                'dashboard.view',
                'reports.*',
                'finance.*',
                'finance.transfers.*',
                'expenses.*',
                'sales.receivables.view',
                'sales.receivables.export',
                'sales.receipts.view',
                'purchases.payables.view',
                'purchases.payables.export',
                'purchases.payments.view',
                'settings.rates.view',
                'settings.rates.export',
            ],
            'rrhh' => [
                'dashboard.view',
                'reports.view',
                'reports.export',
                'payroll.*',
            ],
            'auditor' => self::auditorPermissions(),
        ];
    }

    /**
     * @return list<string>
     */
    private static function auditorPermissions(): array
    {
        $permissions = [];

        foreach (TenantPermission::keys() as $key) {
            if (str_ends_with($key, '.view') || str_ends_with($key, '.export')) {
                $permissions[] = $key;
            }
        }

        $permissions[] = 'settings.audit.view';
        $permissions[] = 'finance.transfers.view';
        $permissions[] = 'settings.audit.export';

        return array_values(array_unique($permissions));
    }
}
