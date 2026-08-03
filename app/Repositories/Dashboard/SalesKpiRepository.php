<?php

namespace App\Repositories\Dashboard;

use Illuminate\Support\Facades\DB;

class SalesKpiRepository
{
    private const INVOICE_TYPE = 'FACV';

    private const CURRENCIES = ['USD', 'COP', 'BS'];

    public function todayByCurrency(): array
    {
        return DB::table('sale_orders')
            ->selectRaw('sls_coin, SUM(sls_total) as total')
            ->where('sls_trans_type_code', self::INVOICE_TYPE)
            ->whereDate('sls_date', now()->toDateString())
            ->groupBy('sls_coin')
            ->pluck('total', 'sls_coin')
            ->map(fn ($total) => (float) $total)
            ->toArray();
    }

    public function dailyBetween(string $from, string $to): array
    {
        $selects = ['sls_date', 'COUNT(*) as count'];

        foreach (self::CURRENCIES as $currency) {
            $selects[] = "SUM(CASE WHEN sls_coin = '{$currency}' THEN sls_total ELSE 0 END) as ".strtolower($currency);
        }

        return DB::table('sale_orders')
            ->selectRaw(implode(', ', $selects))
            ->where('sls_trans_type_code', self::INVOICE_TYPE)
            ->whereBetween('sls_date', [$from, $to])
            ->groupBy('sls_date')
            ->orderBy('sls_date')
            ->get()
            ->map(function (object $row): array {
                $item = [
                    'date' => substr((string) $row->sls_date, 0, 10),
                    'count' => (int) $row->count,
                ];

                foreach (self::CURRENCIES as $currency) {
                    $item[strtolower($currency)] = (float) $row->{strtolower($currency)};
                }

                return $item;
            })
            ->toArray();
    }
}
