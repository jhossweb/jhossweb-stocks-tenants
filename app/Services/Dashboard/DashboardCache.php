<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    private array $items = [];

    public function prefetch(string $month): void
    {
        $keys = $this->allKeys($month);
        $values = Cache::many($keys);

        foreach ($keys as $key) {
            $this->items[$key] = $values[$key] ?? null;
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

    private function allKeys(string $month): array
    {
        $prev = date('Y-m', strtotime("{$month}-01 -1 month"));

        return [
            "dashboard:expenses:total:{$month}",
            'dashboard:expenses:pending',
            "dashboard:expenses:trend:current:{$month}",
            "dashboard:expenses:trend:previous:{$prev}",
            "dashboard:cashflow:inflow:{$month}",
            "dashboard:cashflow:outflow:{$month}",
            'dashboard:receivables:balance',
            "dashboard:receivables:collected:{$month}",
            'dashboard:payables:balance',
            "dashboard:payables:paid:{$month}",
            'dashboard:products:total_skus',
            'dashboard:products:low_stock',
            "dashboard:products:top:{$month}:5",
            "dashboard:products:worst:{$month}:5",
        ];
    }
}
