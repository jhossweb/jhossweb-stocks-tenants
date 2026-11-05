<?php

declare(strict_types=1);

use App\Http\Controllers\Reports\ExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Exportaciones de listados (CSV/PDF)
|--------------------------------------------------------------------------
| Cada ruta define el recurso de exportación y su permiso. El controlador
| resuelve el formato (csv|pdf) mediante ExportManager, con espacio para
| agregar xlsx en el futuro sin cambiar estas rutas.
*/

Route::get('products/export', ExportController::class)
    ->defaults('export', 'inventory.products')
    ->middleware('can:inventory.products.export')
    ->name('products.export');

Route::get('categories/export', ExportController::class)
    ->defaults('export', 'inventory.categories')
    ->middleware('can:inventory.categories.export')
    ->name('categories.export');

Route::get('brands/export', ExportController::class)
    ->defaults('export', 'inventory.brands')
    ->middleware('can:inventory.brands.export')
    ->name('brands.export');

Route::get('warehouses/export', ExportController::class)
    ->defaults('export', 'inventory.warehouses')
    ->middleware('can:inventory.warehouses.export')
    ->name('warehouses.export');

Route::get('inventory/adjustments/export', ExportController::class)
    ->defaults('export', 'inventory.adjustments')
    ->middleware('can:inventory.adjustments.export')
    ->name('inventory.adjustments.export');

Route::get('customers/export', ExportController::class)
    ->defaults('export', 'sales.customers')
    ->middleware('can:sales.customers.export')
    ->name('customers.export');

Route::get('sales/export', ExportController::class)
    ->defaults('export', 'sales.invoices')
    ->middleware('can:sales.invoices.export')
    ->name('sales.export');

Route::get('quotes/export', ExportController::class)
    ->defaults('export', 'sales.quotes')
    ->middleware('can:sales.quotes.export')
    ->name('quotes.export');

Route::get('sale-returns/export', ExportController::class)
    ->defaults('export', 'sales.returns')
    ->middleware('can:sales.returns.export')
    ->name('sale-returns.export');

Route::get('purchases/export', ExportController::class)
    ->defaults('export', 'purchases.invoices')
    ->middleware('can:purchases.invoices.export')
    ->name('purchases.export');

Route::get('purchase-returns/export', ExportController::class)
    ->defaults('export', 'purchases.returns')
    ->middleware('can:purchases.returns.export')
    ->name('purchase-returns.export');

Route::get('suppliers/export', ExportController::class)
    ->defaults('export', 'purchases.suppliers')
    ->middleware('can:purchases.suppliers.export')
    ->name('suppliers.export');

Route::get('expenses/export', ExportController::class)
    ->defaults('export', 'expenses.expenses')
    ->middleware('can:expenses.expenses.export')
    ->name('expenses.export');

Route::get('expense-categories/export', ExportController::class)
    ->defaults('export', 'expenses.categories')
    ->middleware('can:expenses.categories.export')
    ->name('expense-categories.export');

Route::get('employees/export', ExportController::class)
    ->defaults('export', 'payroll.employees')
    ->middleware('can:payroll.employees.export')
    ->name('employees.export');

Route::get('payroll-periods/export', ExportController::class)
    ->defaults('export', 'payroll.periods')
    ->middleware('can:payroll.periods.export')
    ->name('payroll-periods.export');

Route::get('payrolls/export', ExportController::class)
    ->defaults('export', 'payroll.payrolls')
    ->middleware('can:payroll.payrolls.export')
    ->name('payrolls.export');

Route::get('employee-loans/export', ExportController::class)
    ->defaults('export', 'payroll.loans')
    ->middleware('can:payroll.loans.export')
    ->name('employee-loans.export');

Route::get('employee-damages/export', ExportController::class)
    ->defaults('export', 'payroll.damages')
    ->middleware('can:payroll.damages.export')
    ->name('employee-damages.export');

Route::get('bank-accounts/export', ExportController::class)
    ->defaults('export', 'finance.accounts')
    ->middleware('can:finance.accounts.export')
    ->name('bank-accounts.export');

Route::get('cash-closing/export', ExportController::class)
    ->defaults('export', 'finance.cash_closing')
    ->middleware('can:finance.cash_closing.export')
    ->name('cash-closing.export');

Route::get('receipts/export', ExportController::class)
    ->defaults('export', 'sales.receipts')
    ->middleware('can:sales.receipts.export')
    ->name('receipts.export');

Route::get('payments/export', ExportController::class)
    ->defaults('export', 'purchases.payments')
    ->middleware('can:purchases.payments.export')
    ->name('payments.export');

Route::get('accounts-receivable/export', ExportController::class)
    ->defaults('export', 'sales.receivables')
    ->middleware('can:sales.receivables.export')
    ->name('accounts-receivable.export');

Route::get('accounts-payable/export', ExportController::class)
    ->defaults('export', 'purchases.payables')
    ->middleware('can:purchases.payables.export')
    ->name('accounts-payable.export');
