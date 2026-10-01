<?php

namespace App\Http\Controllers;

use App\Repositories\Dashboard\PurchaseKpiRepository;
use App\Repositories\Dashboard\SalesKpiRepository;
use App\Repositories\Inventory\InventoryKpiRepository;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected SalesKpiRepository $salesKpiRepository,
        protected PurchaseKpiRepository $purchaseKpiRepository,
        protected InventoryKpiRepository $inventoryKpiRepository,
    ) {}

    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard', [
            'categoryAggregates' => $this->dashboardService->getCategoryAggregates($month),
            'selectedMonth' => $month,
        ]);
    }

    public function ventasDiarias(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $from = $request->input('from', "{$month}-01");
        $to = $request->input('to', date('Y-m-t', strtotime("{$month}-01")));

        return Inertia::render('Dashboard/VentasDiarias', [
            'sales' => $this->salesKpiRepository->paginateSales($from, $to, 15),
            'today' => $this->salesKpiRepository->todayByCurrency(),
            'selectedMonth' => $month,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function gastos(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard/Gastos', [
            'kpis' => $this->dashboardService->getKpis($month),
            // El desglose por categoría es la parte pesada: va diferido para que
            // las cards de arriba pinten de inmediato.
            'expenseBreakdown' => Inertia::defer(
                fn () => $this->dashboardService->getExpenseBreakdown($month)
            ),
            'selectedMonth' => $month,
        ]);
    }

    public function finanzas(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard/Finanzas', [
            'kpis' => $this->dashboardService->getKpis($month),
            'selectedMonth' => $month,
        ]);
    }

    public function compras(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard/Compras', [
            'kpis' => $this->dashboardService->getKpis($month),
            'selectedMonth' => $month,
        ]);
    }

    public function comprasDiarias(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $from = $request->input('from', "{$month}-01");
        $to = $request->input('to', date('Y-m-t', strtotime("{$month}-01")));

        return Inertia::render('Dashboard/ComprasDiarias', [
            'purchases' => $this->purchaseKpiRepository->paginatePurchases($from, $to, 15),
            'today' => $this->purchaseKpiRepository->todayByCurrency(),
            'selectedMonth' => $month,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function inventario(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard/Inventario', [
            'kpis' => [
                'stockByWarehouse' => $this->inventoryKpiRepository->stockByWarehouse(),
                'inventoryValue' => $this->inventoryKpiRepository->inventoryValue(),
            ],
            'selectedMonth' => $month,
        ]);
    }

    public function inventarioExistencia(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard/Inventario/ExistenciaAlmacen', [
            'stockByWarehouse' => $this->inventoryKpiRepository->stockByWarehouse(),
            'stockDetail' => $this->inventoryKpiRepository->stockDetail(),
            'selectedMonth' => $month,
        ]);
    }

    public function inventarioValor(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard/Inventario/ValorInventario', [
            'inventoryValue' => $this->inventoryKpiRepository->inventoryValue(),
            'valueDetail' => $this->inventoryKpiRepository->inventoryValueDetail(),
            'selectedMonth' => $month,
        ]);
    }
}
