<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCashClosingRequest;
use App\Repositories\Finance\CashClosingRepository;
use App\Services\Finance\CashClosingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashClosingController extends Controller
{
    public function __construct(
        protected CashClosingService $service,
        protected CashClosingRepository $repo,
    ) {}

    public function index(Request $request)
    {
        $history = $this->repo->getHistory(
            dateFrom: $request->input('date_from'),
            dateTo: $request->input('date_to'),
            status: $request->input('status'),
        );

        return Inertia::render('Finance/CashClosing/Index', [
            'history' => $history,
            'filters' => $request->only(['date_from', 'date_to', 'status']),
        ]);
    }

    public function create(Request $request)
    {
        $date = $request->input('cc_date', now()->format('Y-m-d'));
        $opening = (float) ($request->input('cc_opening_balance', 0));

        $preview = fn () => $this->service->calculatePreview($date, $opening);
        $lastClosing = fn () => $this->repo->findClosingByDate(now()->subDay()->format('Y-m-d'));

        return Inertia::render('Finance/CashClosing/Create', [
            'preview' => Inertia::defer($preview),
            'lastClosing' => Inertia::defer($lastClosing),
            'selectedDate' => $date,
        ]);
    }

    public function store(StoreCashClosingRequest $request)
    {
        $this->service->storeClosing(
            date: $request->input('cc_date'),
            openingBalance: (float) $request->input('cc_opening_balance'),
            countedAmount: (float) $request->input('cc_counted_amount'),
            notes: $request->input('cc_notes'),
            movementsData: $request->input('movements', []),
            user: $request->user(),
        );

        return redirect()->route('cash-closing.index')
            ->with('success', 'Cierre de caja guardado exitosamente.');
    }

    public function show(string $date)
    {
        $closing = $this->repo->findClosingByDate($date);

        if ($closing === null) {
            return redirect()->route('cash-closing.index')
                ->with('error', 'No se encontró un cierre para esta fecha.');
        }

        return Inertia::render('Finance/CashClosing/Show', [
            'closing' => $closing,
        ]);
    }
}
