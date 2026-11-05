<?php

declare(strict_types=1);

use App\Http\Controllers\Documents\DocumentLifecycleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ciclo de vida documental (Fase 1)
|--------------------------------------------------------------------------
| Cotizaciones, gastos no pagados y nóminas/períodos no pagados.
| Facturas, compras, devoluciones, cobros y pagos quedan para Fase 2.
*/

foreach ([
    'quotes' => ['label' => 'quotes', 'permission' => 'sales.quotes'],
    'expenses' => ['label' => 'expenses', 'permission' => 'expenses.expenses'],
    'payrolls' => ['label' => 'payrolls', 'permission' => 'payroll.payrolls'],
    'payroll-periods' => ['label' => 'payroll-periods', 'permission' => 'payroll.periods'],
] as $type => $config) {
    Route::patch("{$type}/{id}/activate", [DocumentLifecycleController::class, 'activate'])
        ->defaults('type', $type)
        ->middleware('can:'.$config['permission'].'.activate')
        ->name($type.'.activate');

    Route::patch("{$type}/{id}/deactivate", [DocumentLifecycleController::class, 'deactivate'])
        ->defaults('type', $type)
        ->middleware('can:'.$config['permission'].'.deactivate')
        ->name($type.'.deactivate');

    Route::post("{$type}/{id}/void", [DocumentLifecycleController::class, 'void'])
        ->defaults('type', $type)
        ->middleware('can:'.$config['permission'].'.void')
        ->name($type.'.void');
}

/*
|--------------------------------------------------------------------------
| Anulación Fase 2 (facturas, devoluciones, cobros y pagos)
|--------------------------------------------------------------------------
*/

foreach ([
    'sales' => 'sales.invoices',
    'purchases' => 'purchases.invoices',
    'sale-returns' => 'sales.returns',
    'purchase-returns' => 'purchases.returns',
    'receipts' => 'sales.receipts',
    'payments' => 'purchases.payments',
] as $type => $permission) {
    Route::post("{$type}/{id}/void", [DocumentLifecycleController::class, 'void'])
        ->defaults('type', $type)
        ->middleware('can:'.$permission.'.void')
        ->name($type.'.void');
}

Route::post('inventory-adjustments/{id}/void', [DocumentLifecycleController::class, 'void'])
    ->defaults('type', 'inventory-adjustments')
    ->middleware('can:inventory.adjustments.void')
    ->name('inventory.adjustments.void');
