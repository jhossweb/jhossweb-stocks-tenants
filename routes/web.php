<?php

use App\Http\Controllers\Settings\RateController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\PurchaseReturnController;
use App\Http\Controllers\Purchase\AccountsPayableController;
use App\Http\Controllers\Finance\Bank\BankAccountController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('purchases', PurchaseOrderController::class);
    Route::resource('purchase-returns', PurchaseReturnController::class);
    Route::get('accounts-payable', [AccountsPayableController::class, 'index'])->name('accounts-payable.index');
    Route::resource('bank-accounts', BankAccountController::class);
    // API Routes for Purchase Returns
    Route::prefix('api')->group(function () {
        Route::get('suppliers/{supplier}/unpaid-invoices', [PurchaseReturnController::class, 'getUnpaidInvoices']);
        Route::get('purchase-orders/{purchaseOrder}/items', [PurchaseReturnController::class, 'getPurchaseOrderItems']);
        Route::get('purchase-orders/by-number/{invoice_number}', [PurchaseReturnController::class, 'getPurchaseOrderByNumber']);

    });
});

require __DIR__ . '/settings.php';
