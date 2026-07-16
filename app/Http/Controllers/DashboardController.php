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
            'kpis' => $this->dashboardService->getKpis($month),
            'selectedMonth' => $month,
        ]);
    }
}
