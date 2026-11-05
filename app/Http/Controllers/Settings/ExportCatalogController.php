<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Reports\ExportRegistry;
use Inertia\Inertia;
use Inertia\Response;

class ExportCatalogController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const URLS = [
        'inventory.products' => '/products/export',
        'inventory.categories' => '/categories/export',
        'inventory.brands' => '/brands/export',
        'inventory.warehouses' => '/warehouses/export',
        'inventory.adjustments' => '/inventory/adjustments/export',
        'sales.customers' => '/customers/export',
        'sales.invoices' => '/sales/export',
        'sales.quotes' => '/quotes/export',
        'sales.returns' => '/sale-returns/export',
        'purchases.invoices' => '/purchases/export',
        'purchases.returns' => '/purchase-returns/export',
        'purchases.suppliers' => '/suppliers/export',
        'expenses.expenses' => '/expenses/export',
        'expenses.categories' => '/expense-categories/export',
        'payroll.employees' => '/employees/export',
        'payroll.periods' => '/payroll-periods/export',
        'payroll.payrolls' => '/payrolls/export',
        'payroll.loans' => '/employee-loans/export',
        'payroll.damages' => '/employee-damages/export',
        'finance.accounts' => '/bank-accounts/export',
        'finance.cash_closing' => '/cash-closing/export',
        'sales.receipts' => '/receipts/export',
        'purchases.payments' => '/payments/export',
        'sales.receivables' => '/accounts-receivable/export',
        'purchases.payables' => '/accounts-payable/export',
    ];

    public function index(): Response
    {
        $exports = collect(ExportRegistry::catalog())
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'url' => self::URLS[$key] ?? null,
            ])
            ->values()
            ->all();

        return Inertia::render('settings/Exports', [
            'exports' => $exports,
        ]);
    }
}
