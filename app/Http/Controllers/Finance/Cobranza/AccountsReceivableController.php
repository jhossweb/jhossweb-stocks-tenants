<?php

namespace App\Http\Controllers\Finance\Cobranza;

use App\Http\Controllers\Controller;
use App\Models\AccountsReceivable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountsReceivableController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountsReceivable::with(['saleOrder.customer', 'payments.bankTransaction'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->status($request->status);
        }

        if ($request->has('search')) {
            $query->whereHas('saleOrder', function ($q) use ($request) {
                $q->where('sls_number', 'like', "%{$request->search}%")
                    ->orWhereHas('customer', function ($sq) use ($request) {
                        $sq->where('clt_name', 'like', "%{$request->search}%");
                    });
            });
        }

        $accountsReceivable = $query->paginate(10)->withQueryString();

        return Inertia::render('Sales/AccountsReceivable/Index', [
            'accountsReceivable' => $accountsReceivable,
            'filters' => $request->only(['status', 'search']),
        ]);
    }
}
