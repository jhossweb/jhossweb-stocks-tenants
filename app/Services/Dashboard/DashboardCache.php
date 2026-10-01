<?php

namespace App\Services\Dashboard;

use App\Concerns\ScopesCacheToTenant;
use Illuminate\Support\Facades\Cache;

/**
 * Caché read-through de los KPI de dashboard.
 *
 * Todas las claves pasan por ScopesCacheToTenant: config/tenancy.php desactiva
 * `CacheTenancyBootstrapper` porque CACHE_STORE=file no soporta tags, así que
 * una clave escrita por un tenant la leería cualquier otro. Los KPI son
 * exactamente los datos que no deben filtrarse entre empresas.
 */
class DashboardCache
{
    use ScopesCacheToTenant;

    private const TTL = 60;

    private array $items = [];

    public function prefetch(string $month): void
    {
        $keys = $this->allKeys($month);
        $values = Cache::many(array_map($this->tenantCacheKey(...), $keys));

        foreach ($keys as $index => $key) {
            $this->items[$key] = $values[$this->tenantCacheKey($key)] ?? null;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items) && $this->items[$key] !== null;
    }

    public function set(string $key, mixed $value): void
    {
        Cache::put($this->tenantCacheKey($key), $value, self::TTL);
        $this->items[$key] = $value;
    }

    private function allKeys(string $month): array
    {
        $prev = date('Y-m', strtotime("{$month}-01 -1 month"));

        return [
            // Current month
            "dashboard:expenses:fijo:{$month}",
            "dashboard:expenses:variable:{$month}",
            "dashboard:cashflow:inflow:{$month}",
            "dashboard:cashflow:outflow:{$month}",
            'dashboard:receivables:balance',
            "dashboard:receivables:collected:{$month}",
            'dashboard:payables:balance',
            "dashboard:payables:paid:{$month}",
            "dashboard:profitloss:{$month}",
            "dashboard:purchases:total:{$month}",
            "dashboard:purchases:pending:{$month}",
            "dashboard:purchases:supplier_count:{$month}",
            "dashboard:expenses:by_category:{$month}",
            // Previous month (for trend computation)
            "dashboard:expenses:fijo:{$prev}",
            "dashboard:expenses:variable:{$prev}",
            "dashboard:cashflow:inflow:{$prev}",
            "dashboard:cashflow:outflow:{$prev}",
            "dashboard:profitloss:{$prev}",
            "dashboard:purchases:total:{$prev}",
        ];
    }
}
