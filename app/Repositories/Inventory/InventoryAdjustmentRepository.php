<?php

declare(strict_types=1);

namespace App\Repositories\Inventory;

use App\Concerns\ScopesCacheToTenant;
use App\Models\AdjustmentReason;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Lecturas del módulo de ajustes de inventario.
 *
 * Aísla el shape de los props de Inertia y las queries que eran duplicadas
 * literalmente entre el create y el edit del controller.
 */
class InventoryAdjustmentRepository
{
    use ScopesCacheToTenant;

    private const REASONS_TTL = 3600;

    public const TRANS_TYPE_CODE = 'INV';

    /**
     * Razón de ajuste con stock por almacén, para los selects del formulario.
     *
     * Cachea ARRAYS PLANOS y no modelos a propósito: con los drivers
     * `database`/`file` un Eloquent serializado vuelve como
     * __PHP_Incomplete_Class (ver Company::current()). La clave lleva el
     * tenant_id porque CacheTenancyBootstrapper está desactivado en
     * config/tenancy.php, es decir la caché NO está separada por tenant.
     *
     * @return array<int, array{id: int, adjr_code: string, adjr_name: string, adjr_description: string|null}>
     */
    public function reasons(): array
    {
        return Cache::remember(
            self::reasonsCacheKey(),
            self::REASONS_TTL,
            static fn (): array => AdjustmentReason::query()
                ->ordered()
                ->get(['id', 'adjr_code', 'adjr_name', 'adjr_description'])
                ->map(static fn (AdjustmentReason $reason): array => [
                    'id' => (int) $reason->id,
                    'adjr_code' => $reason->adjr_code,
                    'adjr_name' => $reason->adjr_name,
                    'adjr_description' => $reason->adjr_description,
                ])
                ->all(),
        );
    }

    public static function reasonsCacheKey(): string
    {
        return self::tenantCacheKey('inventory:adjustment_reasons');
    }

    /**
     * Productos con su stock por almacén, solo los que tienen existencias.
     *
     * El stock se carga en UNA consulta agrupada por `stk_pro_code` en vez de
     * una consulta por producto dentro del map (N+1).
     *
     * @return array<int, array<string, mixed>>
     */
    public function formProducts(): array
    {
        $stocksByProduct = $this->positiveStocksByProduct();

        return Product::query()
            ->active()
            ->get(['pro_code', 'pro_name', 'pro_und', 'pro_iva', 'pro_type'])
            ->map(function (Product $product) use ($stocksByProduct): array {
                $stocks = $stocksByProduct->get($product->pro_code, collect())
                    ->map(static fn (Stock $stock): array => [
                        'war_id' => (int) $stock->stk_war_id,
                        'cant' => (float) $stock->stk_cant,
                    ])
                    ->values();

                return [
                    'pro_code' => $product->pro_code,
                    'pro_name' => $product->pro_name,
                    'pro_und' => $product->pro_und?->value,
                    'pro_iva' => $product->pro_iva?->value,
                    'pro_type' => $product->pro_type?->value,
                    'stocks' => $stocks->all(),
                    'total_stock' => (float) $stocks->sum('cant'),
                ];
            })
            ->values()
            ->all();
    }

    public function paginate(int $perPage = 10)
    {
        return Inventory::query()
            ->with(['movements.product', 'movements.warehouse', 'movements.reason', 'transactionType'])
            ->where('inv_trans_type_code', self::TRANS_TYPE_CODE)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Inventory
    {
        return Inventory::query()
            ->with(['movements.product', 'movements.warehouse', 'movements.reason', 'transactionType'])
            ->findOrFail($id);
    }

    /**
     * @return Collection<string, Collection<int, Stock>>
     */
    private function positiveStocksByProduct(): Collection
    {
        return Stock::query()
            ->where('stk_cant', '>', 0)
            ->get(['stk_pro_code', 'stk_war_id', 'stk_cant'])
            ->groupBy('stk_pro_code');
    }
}
