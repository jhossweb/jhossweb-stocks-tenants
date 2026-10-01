<?php

namespace App\Repositories\Dashboard;

use App\Concerns\ResolvesMonthRange;
use App\Models\Expense;
use App\Services\Dashboard\TrendCalculator;
use App\Services\Finance\RateService;
use Illuminate\Support\Facades\DB;

class ExpenseKpiRepository
{
    use ResolvesMonthRange;

    public function __construct(
        protected RateService $rateService,
    ) {}

    public function totalByCurrency(?string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return Expense::query()
            ->selectRaw('exp_coin, SUM(exp_amount) as total, SUM(exp_paid_amount) as paid')
            ->where('exp_date', '>=', $from)
            ->where('exp_date', '<', $to)
            ->groupBy('exp_coin')
            ->pluck('total', 'exp_coin')
            ->toArray();
    }

    public function totalFijoByCurrency(?string $month): array
    {
        return $this->totalByCategoryType('fijo', $month);
    }

    public function totalVariableByCurrency(?string $month): array
    {
        return $this->totalByCategoryType('variable', $month);
    }

    private function totalByCategoryType(string $type, ?string $month): array
    {
        return $this->totalRawByType($month, $type);
    }

    public function pendingBalance(): array
    {
        return Expense::query()
            ->selectRaw('exp_coin, SUM(exp_remaining_balance) as balance')
            ->whereIn('exp_status', ['pendiente', 'parcial'])
            ->groupBy('exp_coin')
            ->pluck('balance', 'exp_coin')
            ->toArray();
    }

    public function trend(string $month): array
    {
        $current = $this->totalRaw($month);
        $previous = $this->totalRaw($this->previousMonth($month));

        return $this->computeTrend($current, $previous);
    }

    public function trendFijo(string $month): array
    {
        return $this->trendByType('fijo', $month);
    }

    public function trendVariable(string $month): array
    {
        return $this->trendByType('variable', $month);
    }

    private function trendByType(string $type, string $month): array
    {
        $current = $this->totalRawByType($month, $type);
        $previous = $this->totalRawByType($this->previousMonth($month), $type);

        return $this->computeTrend($current, $previous);
    }

    private function totalRawByType(?string $month, string $type): array
    {
        [$from, $to] = $this->monthBounds($month);

        return Expense::query()
            ->selectRaw('e.exp_coin, SUM(e.exp_amount) as total')
            ->from('expenses as e')
            ->join('expense_categories as ec', 'ec.id', '=', 'e.exp_category_id')
            ->where('e.exp_date', '>=', $from)
            ->where('e.exp_date', '<', $to)
            ->where('ec.exc_type', $type)
            ->groupBy('e.exp_coin')
            ->pluck('total', 'exp_coin')
            ->toArray();
    }

    private function totalRaw(?string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return Expense::query()
            ->selectRaw('exp_coin, SUM(exp_amount) as total')
            ->where('exp_date', '>=', $from)
            ->where('exp_date', '<', $to)
            ->groupBy('exp_coin')
            ->pluck('total', 'exp_coin')
            ->toArray();
    }

    /**
     * Desglose por categoría en formato plano (una fila por categoría+moneda).
     *
     * Es el contrato que consume `AnalyticsController` y la tabla
     * `Analytics/Expenses.vue`, que espera `exp_coin` y `total` por fila.
     * El dashboard usa en cambio `totalByCategory()`, con el desglose por
     * moneda anidado en `amounts`.
     *
     * @param  string|null  $type  'fijo' o 'variable' para filtrar por tipo.
     * @return array<int, array{exc_name: string, exc_type: string, exp_coin: string, total: float}>
     */
    public function byCategory(?string $month, ?string $type = null): array
    {
        return $this->byCategoryFlat($month, $type);
    }

    /**
     * @param  string  $type  'fijo' o 'variable'.
     * @return array<int, array{exc_name: string, exc_type: string, exp_coin: string, total: float}>
     */
    public function byCategoryType(string $type, ?string $month): array
    {
        return $this->byCategoryFlat($month, $type);
    }

    /**
     * @param  string|null  $type  'fijo' o 'variable' para filtrar por tipo.
     * @return array<int, array{exc_name: string, exc_type: string, exp_coin: string, total: float}>
     */
    private function byCategoryFlat(?string $month, ?string $type = null): array
    {
        [$from, $to] = $this->monthBounds($month);

        // DB::table y no el modelo: `exp_coin` y `exc_type` llegarían casteados
        // a enum, que no pueden indexar el array ni ordenarse sin el ->value.
        $query = DB::table('expenses as e')
            ->join('expense_categories as ec', 'ec.id', '=', 'e.exp_category_id')
            ->selectRaw('ec.exc_name, ec.exc_type, e.exp_coin, SUM(e.exp_amount) as total')
            ->where('e.exp_date', '>=', $from)
            ->where('e.exp_date', '<', $to)
            ->groupBy('ec.id', 'ec.exc_name', 'ec.exc_type', 'e.exp_coin')
            ->orderByDesc('total');

        if ($type !== null) {
            $query->where('ec.exc_type', $type);
        }

        return $query->get()
            ->map(fn ($row): array => [
                'exc_name' => $row->exc_name,
                'exc_type' => $row->exc_type,
                'exp_coin' => $row->exp_coin,
                'total' => round((float) $row->total, 4),
            ])
            ->all();
    }

    /**
     * Totales por categoría de gasto del período.
     *
     * Una fila por categoría (no una por categoría+moneda): el desglose por
     * moneda viaja anidado en `amounts` para que el total de la categoría sea
     * comparable entre filas y convertible a un único equivalente en USD.
     *
     * @param  string|null  $type  'fijo' o 'variable' para filtrar por tipo.
     * @return array<int, array{category_id: int, exc_name: string, exc_type: string, amounts: array<string, float>}>
     */
    public function totalByCategory(?string $month = null, ?string $type = null): array
    {
        [$from, $to] = $this->monthBounds($month);

        // DB::table y no el modelo: es un agregado con join, y `exp_coin` /
        // `exc_type` llegarían casteados a enum, que no se pueden usar como
        // string de array ni concatenar sin sacarles el ->value primero.
        $query = DB::table('expenses as e')
            ->join('expense_categories as ec', 'ec.id', '=', 'e.exp_category_id')
            ->selectRaw('ec.id as ec_id, ec.exc_name, ec.exc_type, e.exp_coin, SUM(e.exp_amount) as total')
            ->where('e.exp_date', '>=', $from)
            ->where('e.exp_date', '<', $to)
            ->groupBy('ec.id', 'ec.exc_name', 'ec.exc_type', 'e.exp_coin')
            ->orderBy('ec.exc_name');

        if ($type !== null) {
            $query->where('ec.exc_type', $type);
        }

        $byCategory = [];

        foreach ($query->get() as $row) {
            $id = (int) $row->ec_id;

            $byCategory[$id] ??= [
                'category_id' => $id,
                'exc_name' => $row->exc_name,
                'exc_type' => $row->exc_type,
                'amounts' => [],
            ];

            $coin = $row->exp_coin;
            $byCategory[$id]['amounts'][$coin] = round(
                ($byCategory[$id]['amounts'][$coin] ?? 0) + (float) $row->total,
                4,
            );
        }

        return array_values($byCategory);
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
