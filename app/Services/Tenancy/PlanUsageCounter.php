<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Enum\Tenancy\TenantLimit;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;

class PlanUsageCounter
{
    /**
     * Calcula el uso real del límite. Los límites mensuales se cuentan sobre
     * `created_at` dentro del mes actual; los totales sobre filas existentes.
     */
    public function count(TenantLimit $limit): int
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return match ($limit) {
            TenantLimit::SalesInvoicesMonthly => SaleOrder::query()
                ->where('sls_trans_type_code', 'FACV')
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            TenantLimit::SalesQuotesMonthly => SaleOrder::query()
                ->where('sls_trans_type_code', 'COT')
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            TenantLimit::SalesReturnsMonthly => SaleOrder::query()
                ->where('sls_trans_type_code', 'DEVV')
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            TenantLimit::PurchasesReturnsMonthly => PurchaseOrder::query()
                ->where('invoice_trans_type_code', 'DEVC')
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            TenantLimit::CustomersMaxTotal => Customer::query()->count(),
            TenantLimit::SuppliersMaxTotal => Supplier::query()->count(),
            TenantLimit::WarehousesMaxTotal => Warehouse::query()->count(),
            TenantLimit::ProductsMaxTotal => Product::query()->count(),
            TenantLimit::UsersMaxTotal => User::query()->where('is_active', true)->count(),
        };
    }
}
