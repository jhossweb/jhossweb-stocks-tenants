<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Cache;

class DashboardCacheInvalidator
{
    public static function receivables(?string $month = null): void
    {
        Cache::forget('dashboard:receivables:balance');
        if ($month) {
            Cache::forget("dashboard:receivables:collected:{$month}");
        }
    }

    public static function payables(?string $month = null): void
    {
        Cache::forget('dashboard:payables:balance');
        if ($month) {
            Cache::forget("dashboard:payables:paid:{$month}");
        }
    }

    public static function purchases(?string $month = null): void
    {
        if ($month) {
            $prev = date('Y-m', strtotime("{$month}-01 -1 month"));
            Cache::forget("dashboard:purchases:total:{$month}");
            Cache::forget("dashboard:purchases:total:{$prev}");
            Cache::forget("dashboard:purchases:pending:{$month}");
            Cache::forget("dashboard:purchases:supplier_count:{$month}");
        }
    }
}
