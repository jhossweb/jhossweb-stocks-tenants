<?php

use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\ExportCatalogController;
use App\Http\Controllers\Settings\MasterDataStatusController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RateController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\TenantAuditController;
use App\Http\Controllers\Settings\TenantPermissionController;
use App\Http\Controllers\Settings\TenantRoleController;
use App\Http\Controllers\Settings\TenantUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'permission.route'])->group(function () {
    Route::redirect('settings', '/settings/profile')->name('settings');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified', 'active.user', 'permission.route'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::resource('settings/rates', RateController::class)->names([
        'index' => 'rates.index',
        'create' => 'rates.create',
        'store' => 'rates.store',
        'show' => 'rates.show',
        'edit' => 'rates.edit',
        'update' => 'rates.update',
        'destroy' => 'rates.destroy',
    ]);

    Route::get('settings/company', [CompanyController::class, 'edit'])->name('company.edit');
    Route::patch('settings/company', [CompanyController::class, 'update'])->name('company.update');

    // Usuarios, roles, permisos y auditoría del tenant.
    // Cada acción se protege con su permiso del catálogo fijo.
    Route::get('settings/users', [TenantUserController::class, 'index'])
        ->middleware('can:settings.users.view')
        ->name('settings.users.index');
    Route::post('settings/users', [TenantUserController::class, 'store'])
        ->middleware('can:settings.users.create')
        ->name('settings.users.store');
    Route::put('settings/users/{user}', [TenantUserController::class, 'update'])
        ->middleware('can:settings.users.update')
        ->name('settings.users.update');
    Route::patch('settings/users/{user}/deactivate', [TenantUserController::class, 'deactivate'])
        ->middleware('can:settings.users.deactivate')
        ->name('settings.users.deactivate');
    Route::patch('settings/users/{user}/activate', [TenantUserController::class, 'activate'])
        ->middleware('can:settings.users.activate')
        ->name('settings.users.activate');

    Route::get('settings/roles', [TenantRoleController::class, 'index'])
        ->middleware('can:settings.roles.view')
        ->name('settings.roles.index');
    Route::post('settings/roles', [TenantRoleController::class, 'store'])
        ->middleware('can:settings.roles.create')
        ->name('settings.roles.store');
    Route::put('settings/roles/{role}', [TenantRoleController::class, 'update'])
        ->middleware('can:settings.roles.update')
        ->name('settings.roles.update');
    Route::delete('settings/roles/{role}', [TenantRoleController::class, 'destroy'])
        ->middleware('can:settings.roles.delete')
        ->name('settings.roles.destroy');

    Route::get('settings/permissions', [TenantPermissionController::class, 'index'])
        ->middleware('can:settings.permissions.view')
        ->name('settings.permissions.index');

    Route::get('settings/exports', [ExportCatalogController::class, 'index'])
        ->name('settings.exports.index');

    Route::get('settings/master-data', [MasterDataStatusController::class, 'index'])
        ->middleware('can:settings.master_data.view')
        ->name('settings.master-data.index');
    Route::patch('settings/master-data/{resource}/{id}/toggle', [MasterDataStatusController::class, 'toggle'])
        ->middleware('can:settings.master_data.update')
        ->name('settings.master-data.toggle');

    Route::get('settings/audit', [TenantAuditController::class, 'index'])
        ->middleware('can:settings.audit.view')
        ->name('settings.audit.index');
    Route::get('settings/audit/export', [TenantAuditController::class, 'export'])
        ->middleware('can:settings.audit.export')
        ->name('settings.audit.export');
});
