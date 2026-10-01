<?php

namespace App\Services\Dashboard;

use App\Concerns\ScopesCacheToTenant;
use Illuminate\Support\Facades\Cache;

/**
 * Invalida claves de la caché de dashboard tras un cambio que las altera.
 *
 * Los métodos son estáticos porque los Observers los invocan sin pasar por el
 * contenedor. El scope por tenant es obligatorio: sin él, un pago de la
 * empresa A borraría (o leería) la caché de la empresa B.
 */
class DashboardCacheInvalidator
{
    use ScopesCacheToTenant;

    public static function receivables(?string $month = null): void
    {
        self::forget('dashboard:receivables:balance');

        if ($month) {
            self::forget("dashboard:receivables:collected:{$month}");
        }
    }

    public static function payables(?string $month = null): void
    {
        self::forget('dashboard:payables:balance');

        if ($month) {
            self::forget("dashboard:payables:paid:{$month}");
        }
    }

    public static function purchases(?string $month = null): void
    {
        if (! $month) {
            return;
        }

        $prev = date('Y-m', strtotime("{$month}-01 -1 month"));

        self::forget("dashboard:purchases:total:{$month}");
        self::forget("dashboard:purchases:total:{$prev}");
        self::forget("dashboard:purchases:pending:{$month}");
        self::forget("dashboard:purchases:supplier_count:{$month}");
    }

    public static function expenses(?string $month = null): void
    {
        if (! $month) {
            return;
        }

        $prev = date('Y-m', strtotime("{$month}-01 -1 month"));

        foreach ([$month, $prev] as $period) {
            self::forget("dashboard:expenses:fijo:{$period}");
            self::forget("dashboard:expenses:variable:{$period}");
            self::forget("dashboard:expenses:by_category:{$period}");
        }
    }

    /**
     * El P&L se deriva de ventas, compras y gastos: lo invalidan los tres.
     */
    public static function profitLoss(?string $month = null): void
    {
        if (! $month) {
            return;
        }

        self::forget("dashboard:profitloss:{$month}");
        self::forget('dashboard:profitloss:'.date('Y-m', strtotime("{$month}-01 -1 month")));
    }

    private static function forget(string $key): void
    {
        Cache::forget(self::tenantCacheKey($key));
    }
}
