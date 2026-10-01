<?php

namespace App\Observers;

use App\Concerns\ScopesCacheToTenant;
use App\Models\Rate;
use Illuminate\Support\Facades\Cache;

/**
 * Invalida la tasa cacheada cuando cambia la tabla.
 *
 * La clave DEBE llevar el mismo scope por tenant que usa RateService; si no,
 * se olvidaría una clave que nadie lee y la tasa vieja seguiría sirviéndose.
 */
class RateObserver
{
    use ScopesCacheToTenant;

    public function created(Rate $rate): void
    {
        self::forgetRates();
    }

    public function updated(Rate $rate): void
    {
        self::forgetRates();
    }

    public function deleted(Rate $rate): void
    {
        self::forgetRates();
    }

    private function forgetRates(): void
    {
        Cache::forget(self::tenantCacheKey('rates:v2'));
    }
}
