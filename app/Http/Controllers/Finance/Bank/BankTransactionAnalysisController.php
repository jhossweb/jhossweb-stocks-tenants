<?php

namespace App\Http\Controllers\Finance\Bank;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\BankTransfer;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankTransactionAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $query = BankTransaction::with([
            'bankAccount',
            'btr_transactionable' => function (MorphTo $morph): void {
                $morph->morphWith([
                    BankTransfer::class => ['sourceAccount:id,bank_name', 'destinationAccount:id,bank_name'],
                ]);
            },
        ])->orderBy('created_at', 'desc');

        if ($request->filled('bank_account_id')) {
            $query->where('btr_bank_account_id', $request->bank_account_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(10)->withQueryString();

        return Inertia::render('Finance/Bank/Analysis/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['bank_account_id', 'date_from', 'date_to']),
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(),
        ]);
    }
}
