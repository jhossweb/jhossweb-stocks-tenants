<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\PurchaseReturnController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('purchases', PurchaseOrderController::class)->only(['index', 'create', 'store']);
    Route::resource('purchase-returns', PurchaseReturnController::class)->only(['index', 'create', 'store']);
    
    // API Routes for Purchase Returns
    Route::prefix('api')->group(function () {
        Route::get('suppliers/{supplier}/unpaid-invoices', [PurchaseReturnController::class, 'getUnpaidInvoices']);
        Route::get('purchase-orders/{purchaseOrder}/items', [PurchaseReturnController::class, 'getPurchaseOrderItems']);
    });
});

require __DIR__.'/settings.php';
