<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Tenancy\BillingRateController;
use App\Http\Controllers\Admin\Tenancy\PlanController;
use App\Http\Controllers\Admin\Tenancy\SubscriptionController;
use App\Http\Controllers\Admin\Tenancy\SubscriptionPaymentController;
use App\Http\Controllers\Admin\Tenancy\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Dashboard placeholder - se implementará en F6
        Route::get('/', fn () => inertia('admin/Dashboard'))->name('dashboard');

        Route::resource('tenants', TenantController::class);
        Route::patch('tenants/{tenant}/update-status', [TenantController::class, 'updateStatus'])->name('tenants.update-status');
        Route::post('tenants/{tenant}/assign-plan', [SubscriptionController::class, 'assignPlan'])->name('tenants.assign-plan');

        // Overrides de límites por tenant (tienen prioridad sobre plans.limits).
        Route::put('tenants/{tenant}/limit-overrides', [TenantController::class, 'updateLimitOverrides'])->name('tenants.limit-overrides.update');

        Route::resource('plans', PlanController::class);

        Route::resource('subscriptions', SubscriptionController::class)->except(['show']);
        Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');

        Route::resource('rates', BillingRateController::class)->except(['show']);

        Route::resource('payments', SubscriptionPaymentController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('payments/{payment}/void', [SubscriptionPaymentController::class, 'void'])->name('payments.void');
        Route::get('payments/{payment}/receipt', [SubscriptionPaymentController::class, 'receipt'])->name('payments.receipt');
    });
