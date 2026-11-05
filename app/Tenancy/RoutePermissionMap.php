<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Enum\Tenancy\TenantPermission;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

final class RoutePermissionMap
{
    /**
     * Rutas de autoservicio o públicas que no dependen de un permiso del
     * catálogo (perfil, seguridad, apariencia, autenticación).
     *
     * @var list<string>
     */
    public const ALLOWLIST = [
        'tenant.root',
        'login',
        'login.store',
        'logout',
        'register',
        'register.store',
        'password.request',
        'password.email',
        'password.reset',
        'password.store',
        'password.confirmation',
        'password.confirm',
        'password.confirm.store',
        'password.update',
        'two-factor.login',
        'two-factor.login.store',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.qr-code',
        'two-factor.secret-key',
        'two-factor.recovery-codes',
        'two-factor.regenerate-recovery-codes',
        'verification.notice',
        'verification.verify',
        'verification.send',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'security.edit',
        'user-password.update',
        'appearance.edit',
        'settings',
        'settings.exports.index',
        'stancl.tenancy.asset',
    ];

    /** @var array<string, string> */
    private const EXACT = [
        'quotes.convert' => 'sales.quotes.convert',
        'quotes.pdf' => 'sales.quotes.export',
        'sales.pdf' => 'sales.invoices.export',
        'accounts-receivable.index' => 'sales.receivables.view',
        'accounts-receivable.by-customer' => 'sales.receivables.view',
        'accounts-receivable.statement' => 'sales.receivables.export',
        'accounts-receivable.export' => 'sales.receivables.export',
        'accounts-payable.index' => 'purchases.payables.view',
        'accounts-payable.by-supplier' => 'purchases.payables.view',
        'accounts-payable.statement' => 'purchases.payables.export',
        'accounts-payable.export' => 'purchases.payables.export',
        'inventory.adjust-prices' => 'inventory.prices.update',
        'inventory.adjust-prices.store' => 'inventory.prices.update',
        'inventory.list-prices' => 'inventory.prices.view',
        'inventory.list-prices.pdf' => 'inventory.prices.export',
        'kardex.index' => 'inventory.kardex.view',
        'analytics.show' => 'reports.view',
        'expenses.pay' => 'expenses.expenses.pay',
        'expenses.cancel-payment' => 'expenses.expenses.cancel_payment',
        'payrolls.generate' => 'payroll.payrolls.generate',
        'payrolls.mark-as-paid' => 'payroll.payrolls.mark_paid',
        'payroll-deductions.store' => 'payroll.deductions.create',
        'payroll-deductions.destroy' => 'payroll.deductions.delete',
        'payroll-payments.store' => 'payroll.payrolls.mark_paid',
        'payroll-payments.create' => 'payroll.payrolls.mark_paid',
        'company.edit' => 'settings.company.view',
        'company.update' => 'settings.company.update',
        'settings.users.deactivate' => 'settings.users.deactivate',
        'settings.master-data.index' => 'settings.master_data.view',
        'settings.master-data.toggle' => 'settings.master_data.update',
        'settings.users.activate' => 'settings.users.activate',
    ];

    /** @var array<string, string> */
    private const PREFIXES = [
        'products' => 'inventory.products',
        'categories' => 'inventory.categories',
        'brands' => 'inventory.brands',
        'warehouses' => 'inventory.warehouses',
        'inventory.adjustments' => 'inventory.adjustments',
        'sales' => 'sales.invoices',
        'sale-returns' => 'sales.returns',
        'customers' => 'sales.customers',
        'receipts' => 'sales.receipts',
        'quotes' => 'sales.quotes',
        'purchases' => 'purchases.invoices',
        'purchase-returns' => 'purchases.returns',
        'suppliers' => 'purchases.suppliers',
        'payments' => 'purchases.payments',
        'bank-accounts' => 'finance.accounts',
        'bank-transaction-analysis' => 'finance.analysis',
        'cash-closing' => 'finance.cash_closing',
        'expenses' => 'expenses.expenses',
        'expense-categories' => 'expenses.categories',
        'employees' => 'payroll.employees',
        'employee-loans' => 'payroll.loans',
        'employee-damages' => 'payroll.damages',
        'payroll-periods' => 'payroll.periods',
        'payrolls' => 'payroll.payrolls',
        'rates' => 'settings.rates',
        'settings.users' => 'settings.users',
        'settings.roles' => 'settings.roles',
        'settings.permissions' => 'settings.permissions',
        'settings.audit' => 'settings.audit',
    ];

    public static function permissionFor(Request $request): ?string
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return null;
        }

        return self::permissionForRoute($route);
    }

    public static function permissionForRoute(Route $route): ?string
    {
        $name = $route->getName();

        if ($name !== null) {
            return self::permissionForName($name);
        }

        return self::permissionForUri($route->uri(), $route->methods());
    }

    public static function isAllowed(Request $request): bool
    {
        $name = $request->route()?->getName();

        return $name !== null && in_array($name, self::ALLOWLIST, true);
    }

    public static function permissionForName(string $name): ?string
    {
        if (in_array($name, self::ALLOWLIST, true)) {
            return null;
        }

        if (isset(self::EXACT[$name])) {
            return self::EXACT[$name];
        }

        if (str_starts_with($name, 'dashboard')) {
            return 'dashboard.view';
        }

        foreach (self::PREFIXES as $prefix => $resource) {
            if (! str_starts_with($name, $prefix.'.')) {
                continue;
            }

            $action = substr($name, strlen($prefix) + 1);
            $mapped = self::actionPermission($resource, $action);

            return $mapped;
        }

        return null;
    }

    /**
     * @param  list<string>  $methods
     */
    public static function permissionForUri(string $uri, array $methods = ['GET']): ?string
    {
        $method = strtoupper($methods[0] ?? 'GET');

        return match (true) {
            $uri === 'api/bank-accounts' && $method === 'POST' => 'finance.accounts.create',
            $uri === 'api/customers' && $method === 'POST' => 'sales.customers.create',
            str_starts_with($uri, 'api/customers/') && str_ends_with($uri, '/pending-receivables') => 'sales.receipts.create',
            str_starts_with($uri, 'api/customers/') && str_ends_with($uri, '/unpaid-sales') => 'sales.returns.create',
            $uri === 'api/suppliers' && $method === 'POST' => 'purchases.suppliers.create',
            str_starts_with($uri, 'api/suppliers/') && str_ends_with($uri, '/pending-invoices') => 'purchases.payments.create',
            str_starts_with($uri, 'api/suppliers/') && str_ends_with($uri, '/unpaid-invoices') => 'purchases.returns.create',
            str_starts_with($uri, 'api/sale-orders/') => 'sales.returns.create',
            str_starts_with($uri, 'api/purchase-orders/') => 'purchases.returns.create',
            str_starts_with($uri, 'api/products/') => 'inventory.prices.view',
            str_starts_with($uri, 'api/kardex/') && str_ends_with($uri, '/pdf') => 'inventory.kardex.export',
            str_starts_with($uri, 'api/kardex/') => 'inventory.kardex.view',
            $uri === 'api/employees/active' => 'payroll.employees.view',
            str_starts_with($uri, 'api/employees/') && str_ends_with($uri, '/loans') => 'payroll.loans.view',
            str_starts_with($uri, 'api/employees/') && str_ends_with($uri, '/damages') => 'payroll.damages.view',
            str_starts_with($uri, 'api/employees/') && str_ends_with($uri, '/pending-payrolls') => 'payroll.payrolls.view',
            $uri === 'api/payroll-periods/pending' => 'payroll.periods.view',
            default => null,
        };
    }

    private static function actionPermission(string $resource, string $action): ?string
    {
        $mapped = match ($action) {
            'index', 'show' => 'view',
            'create', 'store' => 'create',
            'edit', 'update' => 'update',
            'destroy' => 'delete',
            'pdf', 'export' => 'export',
            'activate' => 'activate',
            'deactivate' => 'deactivate',
            'void' => 'void',
            'convert' => 'convert',
            'pay' => 'pay',
            'cancel-payment' => 'cancel_payment',
            'generate' => 'generate',
            'mark-as-paid' => 'mark_paid',
            default => null,
        };

        if ($mapped === null) {
            return null;
        }

        $permission = $resource.'.'.$mapped;

        return TenantPermission::exists($permission) ? $permission : null;
    }
}
