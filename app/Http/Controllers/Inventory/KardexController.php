<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KardexController extends Controller
{
    public function index($proCode)
    {
        $product = Product::where('pro_code', $proCode)->firstOrFail();

        $warehouses = Warehouse::select('id', 'war_name')->get();

        $currentStocks = Stock::where('stk_pro_code', $proCode)
            ->join('warehouses', 'stk_war_id', '=', 'warehouses.id')
            ->select('stk_war_id', 'stk_cant', 'war_name')
            ->get();

        return Inertia::render('Inventory/Kardex/Index', [
            'product' => [
                'pro_code' => $product->pro_code,
                'pro_name' => $product->pro_name,
                'pro_und' => $product->pro_und->value,
                'pro_conversion_factor' => $product->pro_conversion_factor,
                'pro_cant_min' => $product->pro_cant_min,
                'pro_type' => $product->pro_type->value,
            ],
            'warehouses' => $warehouses,
            'currentStocks' => $currentStocks,
        ]);
    }

    public function getYears(Request $request, $proCode)
    {
        $validated = $request->validate(['warehouse_id' => 'required|exists:warehouses,id']);
        $warId = $validated['warehouse_id'];

        $years = DB::select('
            SELECT DISTINCT CAST(strftime(\'%Y\', fecha) AS INTEGER) as year FROM (
                SELECT po.invoice_date as fecha
                FROM movements_purchases mp
                JOIN purchase_orders po ON mp.mov_invoice_number = po.invoice_number
                WHERE mp.mov_pro_code = ? AND mp.mov_war_id = ?
                UNION
                SELECT so.sls_date as fecha
                FROM movements_sales ms
                JOIN sale_orders so ON ms.mvs_number = so.sls_number
                WHERE ms.mvs_pro_code = ? AND ms.mvs_war_id = ?
                UNION
                SELECT i.inv_date as fecha
                FROM movements_inventories mi
                JOIN inventories i ON mi.min_inv_number = i.inv_number
                WHERE mi.min_pro_code = ? AND mi.min_war_id = ?
            ) AS fechas
            ORDER BY year DESC
        ', [$proCode, $warId, $proCode, $warId, $proCode, $warId]);

        return response()->json(array_map(fn ($row) => (int) $row->year, $years));
    }

    public function getMonths(Request $request, $proCode)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'year' => 'required|integer|min:2000|max:2099',
        ]);

        $warId = $validated['warehouse_id'];
        $year = (string) $validated['year'];

        $months = DB::select('
            SELECT DISTINCT CAST(strftime(\'%m\', fecha) AS INTEGER) as month FROM (
                SELECT po.invoice_date as fecha
                FROM movements_purchases mp
                JOIN purchase_orders po ON mp.mov_invoice_number = po.invoice_number
                WHERE mp.mov_pro_code = ? AND mp.mov_war_id = ? AND strftime(\'%Y\', po.invoice_date) = ?
                UNION
                SELECT so.sls_date as fecha
                FROM movements_sales ms
                JOIN sale_orders so ON ms.mvs_number = so.sls_number
                WHERE ms.mvs_pro_code = ? AND ms.mvs_war_id = ? AND strftime(\'%Y\', so.sls_date) = ?
                UNION
                SELECT i.inv_date as fecha
                FROM movements_inventories mi
                JOIN inventories i ON mi.min_inv_number = i.inv_number
                WHERE mi.min_pro_code = ? AND mi.min_war_id = ? AND strftime(\'%Y\', i.inv_date) = ?
            ) AS fechas
            ORDER BY month
        ', [$proCode, $warId, $year, $proCode, $warId, $year, $proCode, $warId, $year]);

        return response()->json(array_map(fn ($row) => (int) $row->month, $months));
    }

    public function getMovements(Request $request, $proCode)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'year' => 'required|integer|min:2000|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $warId = $validated['warehouse_id'];
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $startDate = sprintf('%04d-%02d-01', $year, $month);

        $initialBalance = $this->calculateInitialBalance($proCode, $warId, $startDate);

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
                       ms.mvs_factor as factor,
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

        $runningBalance = $initialBalance;
        $items = array_map(function ($mov) use (&$runningBalance) {
            $mov = (array) $mov;
            $quantity = (float) $mov['cantidad'];
            $factor = (int) $mov['factor'];
            $diff = $quantity * $factor;
            $runningBalance += $diff;
            $mov['cantidad'] = $quantity;
            $mov['factor'] = $factor;
            $mov['entrada'] = (float) $mov['entrada'];
            $mov['salida'] = (float) $mov['salida'];
            $mov['precio_unitario'] = (float) $mov['precio_unitario'];
            $mov['precio_unitario_ref'] = (float) $mov['precio_unitario_ref'];
            $mov['total_documento'] = (float) $mov['total_documento'];
            $mov['saldo'] = round($runningBalance, 4);

            return $mov;
        }, $movements);

        return response()->json([
            'initial_balance' => round($initialBalance, 4),
            'movements' => array_values($items),
        ]);
    }

    public function exportPdf(Request $request, $proCode)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'year' => 'required|integer|min:2000|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $warId = $validated['warehouse_id'];
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $product = Product::where('pro_code', $proCode)->firstOrFail();
        $warehouse = Warehouse::findOrFail($warId);

        $startDate = sprintf('%04d-%02d-01', $year, $month);

        $initialBalance = $this->calculateInitialBalance($proCode, $warId, $startDate);

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
                       ms.mvs_factor as factor,
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

        $runningBalance = $initialBalance;
        $items = array_map(function ($mov) use (&$runningBalance) {
            $m = (array) $mov;
            $quantity = (float) $m['cantidad'];
            $factor = (int) $m['factor'];
            $runningBalance += $quantity * $factor;
            $m['saldo'] = round($runningBalance, 4);
            $m['cantidad'] = $quantity;
            $m['factor'] = $factor;

            return (object) $m;
        }, $movements);

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $data = [
            'product' => $product,
            'warehouse' => $warehouse,
            'mes' => $meses[$month] ?? $month,
            'year' => $year,
            'initialBalance' => round($initialBalance, 4),
            'movements' => $items,
        ];

        $pdf = Pdf::loadView('pdf.kardex', $data);

        $filename = sprintf(
            'kardex_%s_%s_%s_%s.pdf',
            $product->pro_code,
            $warehouse->war_name,
            $year,
            str_pad($month, 2, '0', STR_PAD_LEFT)
        );

        return $pdf->download($filename);
    }

    private function calculateInitialBalance($proCode, $warId, string $startDate): float
    {
        $result = DB::select('
            SELECT COALESCE(SUM(cantidad * factor), 0) as saldo FROM (
                SELECT mp.mov_quantity as cantidad, mp.mov_factor as factor
                FROM movements_purchases mp
                JOIN purchase_orders po ON mp.mov_invoice_number = po.invoice_number
                WHERE mp.mov_pro_code = ? AND mp.mov_war_id = ?
                  AND po.invoice_date < ?
                UNION ALL
                SELECT ms.mvs_quantity as cantidad, ms.mvs_factor as factor
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
            $proCode, $warId, $startDate,
            $proCode, $warId, $startDate,
            $proCode, $warId, $startDate,
        ]);

        return (float) ($result[0]->saldo ?? 0);
    }

    private function nextMonth(string $date): string
    {
        return date('Y-m-d', strtotime($date.' +1 month'));
    }
}
