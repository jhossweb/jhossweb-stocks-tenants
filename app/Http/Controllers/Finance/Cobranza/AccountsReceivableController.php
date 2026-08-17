<?php

namespace App\Http\Controllers\Finance\Cobranza;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Reports\Finance\AccountsReceivableStatementReport;
use App\Repositories\Sales\AccountsReceivable\AccountsReceivableRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountsReceivableController extends Controller
{
    public function __construct(
        protected AccountsReceivableRepository $receivableRepository,
        protected AccountsReceivableStatementReport $statementReport,
    ) {}

    public function index(Request $request)
    {
        $accountsReceivable = $this->receivableRepository->creditPending(
            search: $request->string('search')->toString() ?: null,
            due: $request->string('due')->toString() ?: null,
        );

        $accountsReceivable->each(fn ($account) => $account->saleOrder?->append('days_remaining'));

        return Inertia::render('Sales/AccountsReceivable/Index', [
            'accountsReceivable' => $accountsReceivable,
            'filters' => $request->only(['search', 'due']),
        ]);
    }

    public function exportPdf(Customer $customer)
    {
        return $this->statementReport->download($customer);
    }
}
