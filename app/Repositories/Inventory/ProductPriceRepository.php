<?php

namespace App\Repositories\Inventory;

use App\Models\CostHistory;
use App\Models\PriceHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProductPriceRepository
{
    private const PRICE = 'price_histories';

    private const COST = 'cost_histories';

    /**
     * Columnas de código y fecha por tabla histórica.
     *
     * @var array<string, array{code: string, date: string}>
     */
    private const TABLES = [
        self::PRICE => ['code' => 'price_pro_code', 'date' => 'price_date'],
        self::COST => ['code' => 'cost_pro_code', 'date' => 'cost_date'],
    ];

    /**
     * Lista de precios vigente paginada: precio, utilidad registrada, costo,
     * margen real y variación vs. precio anterior, en una sola consulta.
     *
     * @param  array{q?: ?string, category_id?: ?int|string, brand_id?: ?int|string, sin_precio?: bool|string|null, bajo_costo?: bool|string|null, sort?: ?string}  $filters
     */
    public function paginatedPrices(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        $this->applyCommonFilters($query, $filters);
        $this->applyStateFilters($query, $filters);
        $this->applySort($query, $filters['sort'] ?? null);

        return $query
            ->orderBy('products.pro_code')
            ->paginate($perPage)
            ->through(fn (object $row) => $this->hydrateRow($row));
    }

    /**
     * Filas para la vista de Ajuste de Precios (contrato existente de esa página).
     *
     * @return array<int, array<string, float|int|string>>
     */
    public function adjustmentRows(): array
    {
        return $this->baseQuery()
            ->orderBy('products.pro_name')
            ->get()
            ->map(fn (object $row) => [
                'pro_code' => (string) $row->pro_code,
                'pro_name' => (string) $row->pro_name,
                'last_cost' => (float) ($row->last_cost ?? 0),
                'last_utility' => (float) ($row->last_utility ?? 0),
                'last_price' => (float) ($row->last_price ?? 0),
            ])
            ->all();
    }

    /**
     * Productos con precio vigente para el PDF de clientes (sin costo ni margen).
     *
     * @param  array{q?: ?string, category_id?: ?int|string}  $filters
     * @return array<int, array<string, float|int|string|null>>
     */
    public function clientList(array $filters = []): array
    {
        $query = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.pro_cat_id')
            ->select('products.pro_code', 'products.pro_name', 'products.pro_und', 'categories.cat_name as category')
            ->selectRaw($this->historySub(self::PRICE, 'price_usd').' as price')
            ->selectRaw($this->historySub(self::PRICE, 'price_date').' as price_date');

        $this->applyCommonFilters($query, $filters);
        $query->whereRaw($this->historySub(self::PRICE, 'id').' is not null');

        return $query
            ->orderBy('category')
            ->orderBy('products.pro_name')
            ->get()
            ->map(fn (object $row) => [
                'pro_code' => (string) $row->pro_code,
                'pro_name' => (string) $row->pro_name,
                'pro_und' => (string) $row->pro_und,
                'category' => $row->category ?? 'General',
                'price' => (float) $row->price,
                'price_date' => $row->price_date !== null ? date('Y-m-d', strtotime((string) $row->price_date)) : null,
            ])
            ->all();
    }

    /**
     * Timeline fusionada de precios y costos del producto (más reciente primero).
     *
     * @return array<int, array{type: string, date: string, amount: float, utility: ?float, user: ?string}>
     */
    public function history(string $proCode): array
    {
        $prices = PriceHistory::query()
            ->where('price_pro_code', $proCode)
            ->orderByDesc('price_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['price_usd', 'price_utility', 'price_date', 'price_user_id']);

        $costs = CostHistory::query()
            ->where('cost_pro_code', $proCode)
            ->orderByDesc('cost_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get(['cost_pro_cost_ref', 'cost_date', 'cost_user_id']);

        if ($prices->isEmpty() && $costs->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', $prices->pluck('price_user_id')->merge($costs->pluck('cost_user_id'))->filter()->unique())
            ->pluck('name', 'id');

        $entries = [];

        foreach ($prices as $record) {
            $entries[] = [
                'type' => 'precio',
                'date' => $record->price_date->format('Y-m-d'),
                'amount' => (float) $record->price_usd,
                'utility' => $record->price_utility !== null ? (float) $record->price_utility : null,
                'user' => $users[(int) $record->price_user_id] ?? null,
            ];
        }

        foreach ($costs as $record) {
            $entries[] = [
                'type' => 'costo',
                'date' => $record->cost_date->format('Y-m-d'),
                'amount' => (float) $record->cost_pro_cost_ref,
                'utility' => null,
                'user' => $users[(int) $record->cost_user_id] ?? null,
            ];
        }

        usort($entries, fn (array $a, array $b) => $b['date'] <=> $a['date']);

        return $entries;
    }

    /**
     * Consulta base: productos + último/penúltimo precio y último costo vía subconsulta escalar.
     */
    private function baseQuery(): Builder
    {
        return DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.pro_cat_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.pro_bra_id')
            ->select(
                'products.pro_code',
                'products.pro_name',
                'products.pro_und',
                'categories.cat_name as category',
                'brands.bra_name as brand',
            )
            ->selectRaw($this->historySub(self::PRICE, 'price_usd').' as last_price')
            ->selectRaw($this->historySub(self::PRICE, 'price_utility').' as last_utility')
            ->selectRaw($this->historySub(self::PRICE, 'price_date').' as last_price_date')
            ->selectRaw($this->historySub(self::PRICE, 'price_usd', 1).' as prev_price')
            ->selectRaw($this->historySub(self::COST, 'cost_pro_cost_ref').' as last_cost');
    }

    /**
     * Subconsulta escalar portátil del enésimo registro más reciente de una tabla histórica.
     *
     * Los identificadores provienen de constantes internas; nunca de input del usuario.
     */
    private function historySub(string $table, string $column, int $offset = 0): string
    {
        $config = self::TABLES[$table];
        $alias = $table.($offset > 0 ? '_prev'.$offset : '');

        return sprintf(
            '(select %1$s.%2$s from %3$s %1$s where %1$s.%4$s = products.pro_code order by %1$s.%5$s desc, %1$s.id desc limit 1 offset %6$d)',
            $alias,
            $column,
            $table,
            $config['code'],
            $config['date'],
            $offset,
        );
    }

    /**
     * Filtros compartidos por la lista completa y la versión cliente (PDF).
     *
     * @param  array{q?: ?string, category_id?: ?int|string}  $filters
     */
    private function applyCommonFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            // lower() en ambos lados: LIKE es sensible a mayúsculas en PostgreSQL.
            $term = '%'.mb_strtolower($filters['q']).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->whereRaw('lower(products.pro_code) like ?', [$term])
                    ->orWhereRaw('lower(products.pro_name) like ?', [$term]);
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('products.pro_cat_id', (int) $filters['category_id']);
        }
    }

    /**
     * Filtros exclusivos de la lista completa.
     *
     * @param  array{brand_id?: ?int|string, sin_precio?: bool|string|null, bajo_costo?: bool|string|null}  $filters
     */
    private function applyStateFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['brand_id'])) {
            $query->where('products.pro_bra_id', (int) $filters['brand_id']);
        }

        if (! empty($filters['sin_precio'])) {
            $query->whereRaw($this->historySub(self::PRICE, 'id').' is null');
        }

        if (! empty($filters['bajo_costo'])) {
            $price = $this->historySub(self::PRICE, 'price_usd');
            $query->whereRaw(sprintf(
                'coalesce(%1$s, 0) > 0 and coalesce(%1$s, 0) <= coalesce(%2$s, 0)',
                $price,
                $this->historySub(self::COST, 'cost_pro_cost_ref'),
            ));
        }
    }

    /**
     * Orden permitido por lista blanca; expresiones reutilizan las mismas subconsultas.
     */
    private function applySort(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'precio' => $query
                ->orderByRaw($this->historySub(self::PRICE, 'price_usd').' is null asc')
                ->orderByRaw($this->historySub(self::PRICE, 'price_usd').' desc'),
            'fecha' => $query
                ->orderByRaw($this->historySub(self::PRICE, 'price_date').' is null asc')
                ->orderByRaw($this->historySub(self::PRICE, 'price_date').' desc'),
            default => $query->orderBy('products.pro_name'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function hydrateRow(object $row): array
    {
        $lastCost = $row->last_cost !== null ? (float) $row->last_cost : null;
        $lastPrice = $row->last_price !== null ? (float) $row->last_price : null;
        $prevPrice = $row->prev_price !== null ? (float) $row->prev_price : null;

        return [
            'pro_code' => (string) $row->pro_code,
            'pro_name' => (string) $row->pro_name,
            'pro_und' => (string) $row->pro_und,
            'category' => $row->category,
            'brand' => $row->brand,
            'last_cost' => $lastCost ?? 0.0,
            'last_price' => $lastPrice,
            'last_utility' => $row->last_utility !== null ? (float) $row->last_utility : null,
            'real_margin' => $this->realMargin($lastPrice, $lastCost),
            'variation_pct' => $this->variationPct($lastPrice, $prevPrice),
            'last_price_date' => $row->last_price_date !== null
                ? date('Y-m-d', strtotime((string) $row->last_price_date))
                : null,
        ];
    }

    private function realMargin(?float $price, ?float $cost): ?float
    {
        if ($price === null || $cost === null || $cost == 0.0) {
            return null;
        }

        return round((($price - $cost) / $cost) * 100, 2);
    }

    private function variationPct(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null || $previous == 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
