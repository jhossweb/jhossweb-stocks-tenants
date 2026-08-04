<?php

namespace App\Repositories\Dashboard;

use App\Models\SaleOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SalesKpiRepository
{
    private const INVOICE_TYPE = 'FACV';

    public function todayByCurrency(): array
    {
        $sumFor = function (string $date): array {
            return DB::table('sale_orders')
                ->selectRaw('sls_coin, SUM(sls_total) as total')
                ->where('sls_trans_type_code', self::INVOICE_TYPE)
                ->whereDate('sls_date', $date)
                ->groupBy('sls_coin')
                ->pluck('total', 'sls_coin')
                ->map(fn ($total) => (float) $total)
                ->toArray();
        };

        $today = $sumFor(now()->toDateString());

        if ($today !== []) {
            return $today;
        }

        $lastDate = DB::table('sale_orders')
            ->where('sls_trans_type_code', self::INVOICE_TYPE)
            ->where('sls_date', '<=', now()->toDateString())
            ->max('sls_date');

        if ($lastDate === null) {
            return [];
        }

        return $sumFor(substr((string) $lastDate, 0, 10));
    }

    public function paginateSales(string $from, string $to, int $perPage = 15): LengthAwarePaginator
    {
        return SaleOrder::query()
            ->select([
                'id',
                'sls_number',
                'sls_date',
                'sls_customer_id',
                'sls_coin',
                'sls_total',
                'sls_status',
                'sls_payment_condition',
            ])
            ->with(['customer:id,clt_name'])
            ->where('sls_trans_type_code', self::INVOICE_TYPE)
            ->whereBetween('sls_date', [$from, $to])
            ->orderByDesc('sls_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
