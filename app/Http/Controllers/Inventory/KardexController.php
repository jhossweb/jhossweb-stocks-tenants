<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Reports\Inventory\KardexReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KardexController extends Controller
{
    public function __construct(
        protected KardexReport $kardexReport,
    ) {}

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
            SELECT DISTINCT EXTRACT(YEAR FROM fecha)::int as year FROM (
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
        $year = (int) $validated['year'];

        $months = DB::select('
            SELECT DISTINCT EXTRACT(MONTH FROM fecha)::int as month FROM (
                SELECT po.invoice_date as fecha
                FROM movements_purchases mp
                JOIN purchase_orders po ON mp.mov_invoice_number = po.invoice_number
                WHERE mp.mov_pro_code = ? AND mp.mov_war_id = ? AND EXTRACT(YEAR FROM po.invoice_date) = ?
                UNION
                SELECT so.sls_date as fecha
                FROM movements_sales ms
                JOIN sale_orders so ON ms.mvs_number = so.sls_number
                WHERE ms.mvs_pro_code = ? AND ms.mvs_war_id = ? AND EXTRACT(YEAR FROM so.sls_date) = ?
                UNION
                SELECT i.inv_date as fecha
                FROM movements_inventories mi
                JOIN inventories i ON mi.min_inv_number = i.inv_number
                WHERE mi.min_pro_code = ? AND mi.min_war_id = ? AND EXTRACT(YEAR FROM i.inv_date) = ?
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

        $product = Product::where('pro_code', $proCode)->firstOrFail();

        $data = $this->kardexReport->movements(
            $product,
            (int) $validated['warehouse_id'],
            (int) $validated['year'],
            (int) $validated['month'],
        );

        return response()->json($data);
    }

    public function exportPdf(Request $request, $proCode)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'year' => 'required|integer|min:2000|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $product = Product::where('pro_code', $proCode)->firstOrFail();

        return $this->kardexReport->download(
            $product,
            (int) $validated['warehouse_id'],
            (int) $validated['year'],
            (int) $validated['month'],
        );
    }
}
