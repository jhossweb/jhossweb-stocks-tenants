<?php

use App\Http\Controllers\Employees\EmployeeController;
use App\Http\Controllers\Expense\ExpenseCategoryController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Controllers\Finance\Bank\BankAccountController;
use App\Http\Controllers\Finance\Bank\BankTransactionAnalysisController;
use App\Http\Controllers\Finance\Cobranza\AccountsReceivableController;
use App\Http\Controllers\Finance\Cobranza\ReceiptController;
use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Inventory\PriceAdjustmentController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Payroll\EmployeeDamageController;
use App\Http\Controllers\Payroll\EmployeeLoanController;
use App\Http\Controllers\Payroll\PayrollController;
use App\Http\Controllers\Payroll\PayrollDeductionController;
use App\Http\Controllers\Payroll\PayrollPaymentController;
use App\Http\Controllers\Payroll\PayrollPeriodController;
use App\Http\Controllers\Purchase\AccountPaymentController;
use App\Http\Controllers\Purchase\AccountsPayableController;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\PurchaseReturnController;
use App\Http\Controllers\Sales\Customer\CustomerController;
use App\Http\Controllers\Sales\SaleOrder\SaleOrderController;
use App\Http\Controllers\Sales\SaleReturn\SaleReturnController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// ===========================================================================
// PÚBLICAS
// ===========================================================================
Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// ===========================================================================
// PROTEGIDAS (auth + verified)
// ===========================================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // -----------------------------------------------------------------------
    // DASHBOARD
    // -----------------------------------------------------------------------
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // -----------------------------------------------------------------------
    // COMPRAS
    // -----------------------------------------------------------------------
    Route::resource('purchases', PurchaseOrderController::class);
    Route::resource('purchase-returns', PurchaseReturnController::class);

    // Cuentas por Pagar
    Route::get('accounts-payable', [AccountsPayableController::class, 'index'])->name('accounts-payable.index');

    // Pagos a Proveedores
    Route::get('payments/create', [AccountPaymentController::class, 'create'])->name('payments.create');
    Route::post('payments', [AccountPaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}/edit', [AccountPaymentController::class, 'edit'])->name('payments.edit');
    Route::put('payments/{payment}', [AccountPaymentController::class, 'update'])->name('payments.update');
    Route::delete('payments/{payment}', [AccountPaymentController::class, 'destroy'])->name('payments.destroy');

    // -----------------------------------------------------------------------
    // VENTAS
    // -----------------------------------------------------------------------
    Route::resource('sales', SaleOrderController::class);
    Route::resource('sale-returns', SaleReturnController::class);
    Route::resource('customers', CustomerController::class);

    // Cuentas por Cobrar
    Route::get('accounts-receivable', [AccountsReceivableController::class, 'index'])->name('accounts-receivable.index');

    // Recibos / Cobranzas
    Route::get('receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
    Route::post('receipts', [ReceiptController::class, 'store'])->name('receipts.store');
    Route::get('receipts/{receipt}/edit', [ReceiptController::class, 'edit'])->name('receipts.edit');
    Route::put('receipts/{receipt}', [ReceiptController::class, 'update'])->name('receipts.update');
    Route::delete('receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');

    // -----------------------------------------------------------------------
    // BANCOS
    // -----------------------------------------------------------------------
    Route::resource('bank-accounts', BankAccountController::class);
    Route::get('bank-transaction-analysis', [BankTransactionAnalysisController::class, 'index'])->name('bank-transaction-analysis.index');

    // -----------------------------------------------------------------------
    // PRODUCTOS / INVENTARIO
    // -----------------------------------------------------------------------
    Route::resource('products', ProductController::class)->except(['create', 'edit', 'show']);

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('adjust-prices', [PriceAdjustmentController::class, 'index'])->name('adjust-prices');
        Route::post('adjust-prices', [PriceAdjustmentController::class, 'store'])->name('adjust-prices.store');

        Route::get('adjustments', [InventoryAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('adjustments/create', [InventoryAdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('adjustments', [InventoryAdjustmentController::class, 'store'])->name('adjustments.store');
        Route::get('adjustments/{adjustment}/edit', [InventoryAdjustmentController::class, 'edit'])->name('adjustments.edit');
        Route::put('adjustments/{adjustment}', [InventoryAdjustmentController::class, 'update'])->name('adjustments.update');
        Route::delete('adjustments/{adjustment}', [InventoryAdjustmentController::class, 'destroy'])->name('adjustments.destroy');
    });

    // -----------------------------------------------------------------------
    // GASTOS
    // -----------------------------------------------------------------------
    Route::resource('expense-categories', ExpenseCategoryController::class);
    Route::post('expenses/{expense}/pay', [ExpenseController::class, 'pay'])->name('expenses.pay');
    Route::resource('expenses', ExpenseController::class);

    // -----------------------------------------------------------------------
    // EMPLEADOS
    // -----------------------------------------------------------------------
    Route::resource('employees', EmployeeController::class);

    // -----------------------------------------------------------------------
    // NÓMINA (PAYROLL)
    // -----------------------------------------------------------------------
    Route::resource('payroll-periods', PayrollPeriodController::class);
    Route::post('payrolls/generate/{payrollPeriod}', [PayrollController::class, 'generate'])->name('payrolls.generate');
    Route::post('payrolls/{payroll}/mark-as-paid', [PayrollController::class, 'markAsPaid'])->name('payrolls.mark-as-paid');
    Route::resource('payrolls', PayrollController::class);
    Route::post('payrolls/{payroll}/deductions', [PayrollDeductionController::class, 'store'])->name('payroll-deductions.store');
    Route::delete('payroll-deductions/{payrollDeduction}', [PayrollDeductionController::class, 'destroy'])->name('payroll-deductions.destroy');
    Route::resource('employee-loans', EmployeeLoanController::class);
    Route::resource('employee-damages', EmployeeDamageController::class);

    // Pagos de Nómina (página completa, igual que pagos a proveedores)
    Route::get('payroll-payments/create', [PayrollPaymentController::class, 'create'])->name('payroll-payments.create');
    Route::post('payroll-payments', [PayrollPaymentController::class, 'store'])->name('payroll-payments.store');

    // -----------------------------------------------------------------------
    // API (peticiones AJAX para selects y validaciones)
    // -----------------------------------------------------------------------
    Route::prefix('api')->group(function () {

        // Compras
        Route::get('suppliers/{supplier}/pending-invoices', [AccountPaymentController::class, 'getPendingInvoices']);
        Route::get('suppliers/{supplier}/unpaid-invoices', [PurchaseReturnController::class, 'getUnpaidInvoices']);
        Route::get('purchase-orders/{purchaseOrder}/items', [PurchaseReturnController::class, 'getPurchaseOrderItems']);
        Route::get('purchase-orders/by-number/{invoice_number}', [PurchaseReturnController::class, 'getPurchaseOrderByNumber']);

        // Ventas
        Route::get('customers/{customer}/pending-receivables', [ReceiptController::class, 'getPendingReceivables']);
        Route::get('customers/{customer}/unpaid-sales', [SaleReturnController::class, 'getUnpaidSales']);
        Route::get('sale-orders/{saleOrder}/items', [SaleReturnController::class, 'getSaleOrderItems']);
        Route::get('sale-orders/by-number/{sls_number}', [SaleReturnController::class, 'getSaleOrderByNumber']);

        // Nómina
        Route::get('employees/active', [EmployeeController::class, 'getActiveEmployees']);
        Route::get('employees/{employee}/loans', [EmployeeLoanController::class, 'getActiveLoans']);
        Route::get('employees/{employee}/damages', [EmployeeDamageController::class, 'getActiveDamages']);
        Route::get('employees/{employee}/pending-payrolls', [PayrollPaymentController::class, 'getPendingPayrolls']);
        Route::get('payroll-periods/pending', [PayrollPeriodController::class, 'getPendingPeriods']);
    });
});

require __DIR__.'/settings.php';
