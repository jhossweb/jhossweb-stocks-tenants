<?php

use App\Http\Controllers\Settings\RateController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\PurchaseReturnController;
use App\Http\Controllers\Purchase\AccountsPayableController;
use App\Http\Controllers\Finance\Bank\BankAccountController;
use App\Http\Controllers\Sales\SaleOrderController;
use App\Http\Controllers\Sales\CustomerController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('purchases', PurchaseOrderController::class);
    Route::resource('purchase-returns', PurchaseReturnController::class);
    Route::resource('sales', SaleOrderController::class);
    Route::resource('customers', CustomerController::class);
    Route::get('accounts-payable', [AccountsPayableController::class, 'index'])->name('accounts-payable.index');
    Route::resource('bank-accounts', BankAccountController::class);
    Route::get('payments/create', [\App\Http\Controllers\Purchase\AccountPaymentController::class, 'create'])->name('payments.create');
    Route::post('payments', [\App\Http\Controllers\Purchase\AccountPaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}/edit', [\App\Http\Controllers\Purchase\AccountPaymentController::class, 'edit'])->name('payments.edit');
    Route::put('payments/{payment}', [\App\Http\Controllers\Purchase\AccountPaymentController::class, 'update'])->name('payments.update');
    Route::delete('payments/{payment}', [\App\Http\Controllers\Purchase\AccountPaymentController::class, 'destroy'])->name('payments.destroy');
    Route::resource('products', \App\Http\Controllers\Inventory\ProductController::class)->except(['create', 'edit', 'show']);


    Route::get('inventory/adjust-prices', [\App\Http\Controllers\Inventory\PriceAdjustmentController::class, 'index'])->name('inventory.adjust-prices');
    Route::post('inventory/adjust-prices', [\App\Http\Controllers\Inventory\PriceAdjustmentController::class, 'store'])->name('inventory.adjust-prices.store');

    // API Routes for Finance/Tesoreria
    Route::prefix('api')->group(function () {
        Route::get('suppliers/{supplier}/pending-invoices', [\App\Http\Controllers\Purchase\AccountPaymentController::class, 'getPendingInvoices']);
        Route::get('suppliers/{supplier}/unpaid-invoices', [PurchaseReturnController::class, 'getUnpaidInvoices']);
        Route::get('purchase-orders/{purchaseOrder}/items', [PurchaseReturnController::class, 'getPurchaseOrderItems']);
        Route::get('purchase-orders/by-number/{invoice_number}', [PurchaseReturnController::class, 'getPurchaseOrderByNumber']);
    });
});

require __DIR__ . '/settings.php';
