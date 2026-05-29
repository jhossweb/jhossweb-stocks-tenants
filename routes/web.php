<?php

use App\Http\Controllers\Finance\Bank\BankAccountController;
use App\Http\Controllers\Inventory\PriceAdjustmentController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Purchase\AccountPaymentController;
use App\Http\Controllers\Purchase\AccountsPayableController;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\PurchaseReturnController;
use App\Http\Controllers\Finance\Cobranza\ReceiptController;
use App\Http\Controllers\Finance\Cobranza\AccountsReceivableController;
use App\Http\Controllers\Sales\Customer\CustomerController;
use App\Http\Controllers\Sales\SaleOrder\SaleOrderController;
use App\Http\Controllers\Sales\SaleReturn\SaleReturnController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('purchases', PurchaseOrderController::class);
    Route::resource('purchase-returns', PurchaseReturnController::class);
    Route::resource('sales', SaleOrderController::class);
    Route::resource('sale-returns', SaleReturnController::class);
    Route::resource('customers', CustomerController::class);
    Route::get('accounts-payable', [AccountsPayableController::class, 'index'])->name('accounts-payable.index');
    Route::resource('bank-accounts', BankAccountController::class);
    Route::get('payments/create', [AccountPaymentController::class, 'create'])->name('payments.create');
    Route::post('payments', [AccountPaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}/edit', [AccountPaymentController::class, 'edit'])->name('payments.edit');
    Route::put('payments/{payment}', [AccountPaymentController::class, 'update'])->name('payments.update');
    Route::delete('payments/{payment}', [AccountPaymentController::class, 'destroy'])->name('payments.destroy');

    Route::get('accounts-receivable', [AccountsReceivableController::class, 'index'])->name('accounts-receivable.index');
    Route::get('receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
    Route::post('receipts', [ReceiptController::class, 'store'])->name('receipts.store');
    Route::get('receipts/{receipt}/edit', [ReceiptController::class, 'edit'])->name('receipts.edit');
    Route::put('receipts/{receipt}', [ReceiptController::class, 'update'])->name('receipts.update');
    Route::delete('receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');

    Route::resource('products', ProductController::class)->except(['create', 'edit', 'show']);

    Route::get('inventory/adjust-prices', [PriceAdjustmentController::class, 'index'])->name('inventory.adjust-prices');
    Route::post('inventory/adjust-prices', [PriceAdjustmentController::class, 'store'])->name('inventory.adjust-prices.store');

    // API Routes for Finance
    Route::prefix('api')->group(function () {
        Route::get('suppliers/{supplier}/pending-invoices', [AccountPaymentController::class, 'getPendingInvoices']);
        Route::get('customers/{customer}/pending-receivables', [ReceiptController::class, 'getPendingReceivables']);
        Route::get('suppliers/{supplier}/unpaid-invoices', [PurchaseReturnController::class, 'getUnpaidInvoices']);
        Route::get('purchase-orders/{purchaseOrder}/items', [PurchaseReturnController::class, 'getPurchaseOrderItems']);
        Route::get('purchase-orders/by-number/{invoice_number}', [PurchaseReturnController::class, 'getPurchaseOrderByNumber']);
        Route::get('customers/{customer}/unpaid-sales', [SaleReturnController::class, 'getUnpaidSales']);
        Route::get('sale-orders/{saleOrder}/items', [SaleReturnController::class, 'getSaleOrderItems']);
        Route::get('sale-orders/by-number/{sls_number}', [SaleReturnController::class, 'getSaleOrderByNumber']);
    });
});

require __DIR__.'/settings.php';
