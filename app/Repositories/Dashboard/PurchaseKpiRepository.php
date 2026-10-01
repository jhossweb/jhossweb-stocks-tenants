<?php

namespace App\Repositories\Dashboard;

use App\Concerns\ResolvesMonthRange;
use App\Models\PurchaseOrder;
use App\Services\Dashboard\TrendCalculator;
use App\Services\Finance\RateService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseKpiRepository
{
    use ResolvesMonthRange;

    private const ACTIVE_STATUSES = ['pending', 'partial', 'paid'];

    public function __construct(
        protected RateService $rateService,
    ) {}

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
            // Intervalo semiabierto: 'to' llega como Y-m-d y en SQLite la fecha
            // se guarda con hora, así que <= $to dejaría fuera todo el último día.
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<', CarbonImmutable::parse($to)->addDay()->toDateString())
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function totalByCurrency(?string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return PurchaseOrder::query()
            ->selectRaw('invoice_coin, SUM(invoice_total) as total')
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<', $to)
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('invoice_coin')
            ->pluck('total', 'invoice_coin')
            ->toArray();
    }

    public function pendingByCurrency(?string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return PurchaseOrder::query()
            ->selectRaw('invoice_coin, SUM(invoice_total) as total')
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<', $to)
            ->whereIn('invoice_status', ['pending', 'partial'])
            ->groupBy('invoice_coin')
            ->pluck('total', 'invoice_coin')
            ->toArray();
    }

    public function countSuppliers(?string $month): int
    {
        [$from, $to] = $this->monthBounds($month);

        return PurchaseOrder::query()
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<', $to)
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->distinct('invoice_supplier_id')
            ->count('invoice_supplier_id');
    }

    public function bySupplier(?string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return PurchaseOrder::query()
            ->selectRaw('s.supplier_name, po.invoice_coin, SUM(po.invoice_total) as total')
            ->from('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.invoice_supplier_id')
            ->where('po.invoice_date', '>=', $from)
            ->where('po.invoice_date', '<', $to)
            ->whereIn('po.invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('s.id', 'po.invoice_coin')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Compras agrupadas por mes, en la ventana de los últimos $limit meses.
     *
     * El agrupado por mes se hace en PHP sobre un rango de fechas: en SQL
     * implicaría una función de formato de fecha que no es portable.
     *
     * @return array<int, array{month: string, invoice_coin: string, total: float}>
     */
    public function byMonth(?int $limit = 6, ?string $endMonth = null): array
    {
        $limit ??= 6;
        $end = CarbonImmutable::createFromFormat('Y-m', $endMonth ?? now()->format('Y-m'))->startOfMonth();
        $start = $end->subMonths($limit - 1)->startOfMonth();

        $rows = PurchaseOrder::query()
            ->selectRaw('invoice_date, invoice_coin, SUM(invoice_total) as total')
            ->where('invoice_date', '>=', $start->toDateString())
            ->where('invoice_date', '<', $end->addMonthNoOverflow()->startOfMonth()->toDateString())
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('invoice_date', 'invoice_coin')
            ->get();

        $series = [];

        foreach ($rows as $row) {
            $month = substr((string) $row->invoice_date, 0, 7);
            $coin = (string) $row->invoice_coin;

            $series[$month][$coin] = round(($series[$month][$coin] ?? 0) + (float) $row->total, 4);
        }

        krsort($series);

        $result = [];

        foreach ($series as $month => $coins) {
            foreach ($coins as $coin => $total) {
                $result[] = ['month' => $month, 'invoice_coin' => $coin, 'total' => $total];
            }
        }

        return $result;
    }

    public function byStatus(?string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return PurchaseOrder::query()
            ->selectRaw('invoice_status, invoice_coin, SUM(invoice_total) as total')
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<', $to)
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('invoice_status', 'invoice_coin')
            ->orderBy('invoice_status')
            ->get()
            ->toArray();
    }

    public function allPurchases(?string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return PurchaseOrder::query()
            ->selectRaw('po.invoice_number, po.invoice_date, s.supplier_name, po.invoice_coin, po.invoice_total, po.invoice_status')
            ->from('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.invoice_supplier_id')
            ->where('po.invoice_date', '>=', $from)
            ->where('po.invoice_date', '<', $to)
            ->whereIn('po.invoice_status', ['pending', 'partial', 'paid'])
            ->orderByDesc('po.invoice_date')
            ->get()
            ->toArray();
    }

    public function trend(string $month): array
    {
        $current = $this->totalRaw($month);
        $previous = $this->totalRaw($this->previousMonth($month));

        return $this->computeTrend($current, $previous);
    }

    private function totalRaw(?string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return PurchaseOrder::query()
            ->selectRaw('invoice_coin, SUM(invoice_total) as total')
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<', $to)
            ->whereIn('invoice_status', ['pending', 'partial', 'paid'])
            ->groupBy('invoice_coin')
            ->pluck('total', 'invoice_coin')
            ->toArray();
    }

    /**
     * @param  array<string, float>  $current
     * @param  array<string, float>  $previous
     * @return array{direction: string, percentage: float, reliable: bool}
     */
    private function computeTrend(array $current, array $previous): array
    {
        return TrendCalculator::compute($current, $previous, $this->rateService->getLatestRate());
    }
}
