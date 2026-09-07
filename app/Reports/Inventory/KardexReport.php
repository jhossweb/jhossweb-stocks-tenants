<?php

namespace App\Reports\Inventory;

use App\Models\Product;
use App\Models\Warehouse;
use App\Reports\PdfReport;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class KardexReport extends PdfReport
{
    public function movements(Product $product, int $warehouseId, int $year, int $month): array
    {
        $conversionFactor = $this->getConversionFactor($product);
        $startDate = sprintf('%04d-%02d-01', $year, $month);

        $initialBalance = $this->calculateInitialBalance($product->pro_code, $warehouseId, $startDate, $conversionFactor);

        $movements = $this->queryMovements($product->pro_code, $warehouseId, $startDate, $conversionFactor);

        $runningBalance = $initialBalance;
        $items = array_map(function ($mov) use (&$runningBalance) {
            $m = (array) $mov;
            $quantity = (float) $m['cantidad'];
            $factor = (int) $m['factor'];
            $runningBalance += $quantity * $factor;
            $m['cantidad'] = $quantity;
            $m['factor'] = $factor;
            $m['entrada'] = (float) $m['entrada'];
            $m['salida'] = (float) $m['salida'];
            $m['precio_unitario'] = (float) $m['precio_unitario'];
            $m['precio_unitario_ref'] = (float) $m['precio_unitario_ref'];
            $m['total_documento'] = (float) $m['total_documento'];
            $m['saldo'] = round($runningBalance, 4);

            return (object) $m;
        }, $movements);

        return [
            'initial_balance' => round($initialBalance, 4),
            'movements' => array_values($items),
        ];
    }

    public function download(Product $product, int $warehouseId, int $year, int $month): Response
    {
        $warehouse = Warehouse::findOrFail($warehouseId);

        $data = $this->movements($product, $warehouseId, $year, $month);

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $viewData = [
            'product' => $product,
            'warehouse' => $warehouse,
            'mes' => $meses[$month] ?? $month,
            'year' => $year,
            'initialBalance' => $data['initial_balance'],
            'movements' => $data['movements'],
        ];

        $filename = $this->buildFilename('kardex', implode('_', [
            $product->pro_code,
            $warehouse->war_name,
            $year,
            str_pad($month, 2, '0', STR_PAD_LEFT),
        ]));

        return $this->make('pdf.kardex', $viewData, $filename);
    }

    /**
     * @return array<int, object>
     */
    private function queryMovements(string $proCode, int $warId, string $startDate, float $conversionFactor): array
    {
        $movements = DB::select("
            SELECT fecha, documento, tipo_transaccion, factor, cantidad, contraparte,
                   CASE WHEN factor = 1 THEN cantidad ELSE 0 END as entrada,
                   CASE WHEN factor = -1 THEN cantidad ELSE 0 END as salida,
                   precio_unitario, precio_unitario_ref, total_documento, moneda, origen
            FROM (
                SELECT po.invoice_date as fecha,
                       mp.mov_invoice_number as documento,
                       tt.trans_type_description as tipo_transaccion,
                       mp.mov_factor as factor,
                       mp.mov_quantity as cantidad,
                       s.supplier_name as contraparte,
                       mp.mov_unit_cost as precio_unitario,
                       mp.mov_unit_cost_ref as precio_unitario_ref,
                       po.invoice_total as total_documento,
                       po.invoice_coin as moneda,
                       'compra' as origen,
                       mp.created_at as created_at
                FROM movements_purchases mp
                JOIN purchase_orders po ON mp.mov_invoice_number = po.invoice_number
                JOIN suppliers s ON po.invoice_supplier_id = s.id
                LEFT JOIN transaction_types tt ON mp.mov_invoice_trans_type_code = tt.trans_type_code
                WHERE mp.mov_pro_code = ? AND mp.mov_war_id = ?
                  AND po.invoice_date >= ? AND po.invoice_date < ?

                UNION ALL

                SELECT so.sls_date as fecha,
                       ms.mvs_number as documento,
                       tt.trans_type_description as tipo_transaccion,
                       CAST(ms.mvs_factor AS INTEGER) as factor,
                       ms.mvs_quantity as cantidad,
                       c.clt_name as contraparte,
                       ms.mvs_unit_price as precio_unitario,
                       ms.mvs_unit_price_ref as precio_unitario_ref,
                       so.sls_total as total_documento,
                       so.sls_coin as moneda,
                       'venta' as origen,
                       ms.created_at as created_at
                FROM movements_sales ms
                JOIN sale_orders so ON ms.mvs_number = so.sls_number
                JOIN customers c ON so.sls_customer_id = c.id
                LEFT JOIN transaction_types tt ON ms.mvs_trans_type_code = tt.trans_type_code
                WHERE ms.mvs_pro_code = ? AND ms.mvs_war_id = ?
                  AND so.sls_date >= ? AND so.sls_date < ?

                UNION ALL

                SELECT i.inv_date as fecha,
                       mi.min_inv_number as documento,
                       tt.trans_type_description as tipo_transaccion,
                       mi.min_factor as factor,
                       mi.min_quantity as cantidad,
                       ar.adjr_name as contraparte,
                       0 as precio_unitario,
                       0 as precio_unitario_ref,
                       0 as total_documento,
                       NULL as moneda,
                       'ajuste' as origen,
                       mi.created_at as created_at
                FROM movements_inventories mi
                JOIN inventories i ON mi.min_inv_number = i.inv_number
                JOIN adjustment_reasons ar ON mi.min_reason_id = ar.id
                LEFT JOIN transaction_types tt ON mi.min_trans_type_code = tt.trans_type_code
                WHERE mi.min_pro_code = ? AND mi.min_war_id = ?
                  AND i.inv_date >= ? AND i.inv_date < ?
            ) AS mov
            ORDER BY fecha, created_at
        ", [
            $proCode, $warId, $startDate, $this->nextMonth($startDate),
            $proCode, $warId, $startDate, $this->nextMonth($startDate),
            $proCode, $warId, $startDate, $this->nextMonth($startDate),
        ]);

        if ($conversionFactor > 1) {
            foreach ($movements as $mov) {
                if ($mov->origen === 'compra') {
                    $mov->cantidad = (float) $mov->cantidad * $conversionFactor;
                    $mov->entrada = (float) $mov->entrada * $conversionFactor;
                    $mov->salida = (float) $mov->salida * $conversionFactor;
                }
            }
        }

        return $movements;
    }

    private function calculateInitialBalance(string $proCode, int $warId, string $startDate, float $conversionFactor = 1): float
    {
        $result = DB::select('
            SELECT COALESCE(SUM(cantidad * factor), 0) as saldo FROM (
                SELECT (mp.mov_quantity * ?) as cantidad, mp.mov_factor as factor
                FROM movements_purchases mp
                JOIN purchase_orders po ON mp.mov_invoice_number = po.invoice_number
                WHERE mp.mov_pro_code = ? AND mp.mov_war_id = ?
                  AND po.invoice_date < ?
                UNION ALL
                SELECT ms.mvs_quantity as cantidad, CAST(ms.mvs_factor AS INTEGER) as factor
                FROM movements_sales ms
                JOIN sale_orders so ON ms.mvs_number = so.sls_number
                WHERE ms.mvs_pro_code = ? AND ms.mvs_war_id = ?
                  AND so.sls_date < ?
                UNION ALL
                SELECT mi.min_quantity as cantidad, mi.min_factor as factor
                FROM movements_inventories mi
                JOIN inventories i ON mi.min_inv_number = i.inv_number
                WHERE mi.min_pro_code = ? AND mi.min_war_id = ?
                  AND i.inv_date < ?
            ) AS balance
        ', [
            $conversionFactor,
            $proCode, $warId, $startDate,
            $proCode, $warId, $startDate,
            $proCode, $warId, $startDate,
        ]);

        return (float) ($result[0]->saldo ?? 0);
    }

    private function getConversionFactor(Product $product): float
    {
        if (in_array($product->pro_und->value, ['mts', 'kg', 'm2'])) {
            return (float) ($product->pro_conversion_factor ?? 1);
        }

        return 1;
    }

    private function nextMonth(string $date): string
    {
        return date('Y-m-d', strtotime($date.' +1 month'));
    }
}
