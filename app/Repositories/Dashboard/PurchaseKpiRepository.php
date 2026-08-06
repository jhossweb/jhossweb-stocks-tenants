<?php

namespace App\Repositories\Dashboard;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseKpiRepository
{
    private const ACTIVE_STATUSES = ['pending', 'partial', 'paid'];

    public function todayByCurrency(): array
    {
        $sumFor = function (string $date): array {
            return DB::table('purchase_orders')
                ->selectRaw('invoice_coin, SUM(invoice_total) as total')
                ->whereDate('invoice_date', $date)
                ->whereIn('invoice_status', self::ACTIVE_STATUSES)
                ->groupBy('invoice_coin')
                ->pluck('total', 'invoice_coin')
                ->map(fn ($total) => (float) $total)
                ->toArray();
        };

        $today = $sumFor(now()->toDateString());

        if ($today !== []) {
            return $today;
        }

        $lastDate = DB::table('purchase_orders')
            ->whereDate('invoice_date', '<=', now()->toDateString())
            ->whereIn('invoice_status', self::ACTIVE_STATUSES)
            ->max('invoice_date');

        if ($lastDate === null) {
            return [];
        }

        return $sumFor(substr((string) $lastDate, 0, 10));
    }

    public function paginatePurchases(string $from, string $to, int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->select([
                'id',
                'invoice_number',
                'invoice_date',
                'invoice_supplier_id',
                'invoice_coin',
                'invoice_total',
                'invoice_status',
                'invoice_payment_condition',
            ])
            ->with(['supplier:id,supplier_name'])
            ->whereIn('invoice_status', self::ACTIVE_STATUSES)
            ->whereBetween('invoice_date', [$from, $to])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function totalByCurrency(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return PurchaseOrder::query()
            ->selectRaw('invoice_coin, SUM(invoice_total) as total')
            ->whereRaw("TO_CHAR(invoice_date, 'YYYY-MM') = ?", [$month])
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('invoice_coin')
            ->pluck('total', 'invoice_coin')
            ->toArray();
    }

    public function pendingByCurrency(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return PurchaseOrder::query()
            ->selectRaw('invoice_coin, SUM(invoice_total) as total')
            ->whereRaw("TO_CHAR(invoice_date, 'YYYY-MM') = ?", [$month])
            ->whereIn('invoice_status', ['pending', 'partial'])
            ->groupBy('invoice_coin')
            ->pluck('total', 'invoice_coin')
            ->toArray();
    }

    public function countSuppliers(?string $month): int
    {
        $month ??= now()->format('Y-m');

        return PurchaseOrder::query()
            ->whereRaw("TO_CHAR(invoice_date, 'YYYY-MM') = ?", [$month])
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->distinct('invoice_supplier_id')
            ->count('invoice_supplier_id');
    }

    public function bySupplier(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return PurchaseOrder::query()
            ->selectRaw('s.supplier_name, po.invoice_coin, SUM(po.invoice_total) as total')
            ->from('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.invoice_supplier_id')
            ->whereRaw("TO_CHAR(po.invoice_date, 'YYYY-MM') = ?", [$month])
            ->whereIn('po.invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('s.id', 'po.invoice_coin')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function byMonth(?int $limit = 6): array
    {
        return PurchaseOrder::query()
            ->selectRaw("TO_CHAR(invoice_date, 'YYYY-MM') as month, invoice_coin, SUM(invoice_total) as total")
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('month', 'invoice_coin')
            ->orderByDesc('month')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function byStatus(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return PurchaseOrder::query()
            ->selectRaw('invoice_status, invoice_coin, SUM(invoice_total) as total')
            ->whereRaw("TO_CHAR(invoice_date, 'YYYY-MM') = ?", [$month])
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('invoice_status', 'invoice_coin')
            ->orderBy('invoice_status')
            ->get()
            ->toArray();
    }

    public function allPurchases(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return PurchaseOrder::query()
            ->selectRaw('po.invoice_number, po.invoice_date, s.supplier_name, po.invoice_coin, po.invoice_total, po.invoice_status')
            ->from('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.invoice_supplier_id')
            ->whereRaw("TO_CHAR(po.invoice_date, 'YYYY-MM') = ?", [$month])
            ->whereIn('po.invoice_status', ['pending', 'partial', 'paid'])
            ->orderByDesc('po.invoice_date')
            ->get()
            ->toArray();
    }

    public function trend(string $month): array
    {
        $previousMonth = date('Y-m', strtotime("{$month}-01 -1 month"));

        $current = $this->totalRaw($month);
        $previous = $this->totalRaw($previousMonth);

        return $this->computeTrend($current, $previous);
    }

    private function totalRaw(string $month): array
    {
        return PurchaseOrder::query()
            ->selectRaw('invoice_coin, SUM(invoice_total) as total')
            ->whereRaw("TO_CHAR(invoice_date, 'YYYY-MM') = ?", [$month])
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('invoice_coin')
            ->pluck('total', 'invoice_coin')
            ->toArray();
    }

    private function computeTrend(array $current, array $previous): array
    {
        $totalCurrent = array_sum($current);
        $totalPrevious = array_sum($previous);

        if ($totalPrevious <= 0) {
            return ['direction' => 'flat', 'percentage' => 0];
        }

        $percentage = round((($totalCurrent - $totalPrevious) / $totalPrevious) * 100, 1);

        return [
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'flat'),
            'percentage' => abs($percentage),
        ];
    }
}
