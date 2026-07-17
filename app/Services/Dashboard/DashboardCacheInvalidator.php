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

    public static function products(?string $month = null): void
    {
        Cache::forget('dashboard:products:total_skus');
        Cache::forget('dashboard:products:low_stock');
        if ($month) {
            $prev = date('Y-m', strtotime("{$month}-01 -1 month"));
            foreach ([$month, $prev] as $m) {
                Cache::forget("dashboard:products:top:{$m}:5");
                Cache::forget("dashboard:products:worst:{$m}:5");
                Cache::forget("dashboard:products:top:{$m}:10");
                Cache::forget("dashboard:products:worst:{$m}:10");
            }
        }
    }
}
