<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard', [
            'categoryAggregates' => $this->dashboardService->getCategoryAggregates($month),
            'selectedMonth' => $month,
        ]);
    }

    public function ventas(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard/Ventas', [
            'kpis' => $this->dashboardService->getKpis($month),
            'selectedMonth' => $month,
        ]);
    }

    public function gastos(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Dashboard/Gastos', [
            'kpis' => $this->dashboardService->getKpis($month),
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
}
