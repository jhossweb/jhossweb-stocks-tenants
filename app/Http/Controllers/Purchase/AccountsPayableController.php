<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Reports\Finance\AccountsPayableStatementReport;
use App\Repositories\Purchases\AccountsPayable\AccountsPayableRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountsPayableController extends Controller
{
    public function __construct(
        protected AccountsPayableRepository $payableRepository,
        protected AccountsPayableStatementReport $statementReport,
    ) {}

    /**
     * Display a listing of the accounts payable.
     */
    public function index(Request $request)
    {
        $accountsPayable = $this->payableRepository->pendingWithFilters(
            search: $request->string('search')->toString() ?: null,
            status: $request->string('status')->toString() ?: null,
        );

        return Inertia::render('purchases/AccountsPayable/Index', [
            'accountsPayable' => $accountsPayable,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function bySupplier(Request $request)
    {
        $summaries = $this->payableRepository->summaryBySupplier(
            search: $request->string('search')->toString() ?: null,
        );

        return Inertia::render('purchases/AccountsPayable/BySupplier', [
            'summaries' => $summaries,
            'filters' => $request->only(['search']),
        ]);
    }

    public function exportPdf(Supplier $supplier)
    {
        return $this->statementReport->download($supplier);
    }
}
