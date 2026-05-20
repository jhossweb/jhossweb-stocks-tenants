<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\AccountsPayable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountsPayableController extends Controller
{
    /**
     * Display a listing of the accounts payable.
     */
    public function index(Request $request)
    {
        $query = AccountsPayable::with(['purchaseOrder.supplier', 'payments.bankTransaction'])
            ->orderBy('created_at', 'desc');

        // Apply filters if needed
        if ($request->has('status')) {
            $query->status($request->status);
        }

        if ($request->has('search')) {
            $query->whereHas('purchaseOrder', function ($q) use ($request) {
                $q->where('invoice_number', 'like', "%{$request->search}%")
                    ->orWhereHas('supplier', function ($sq) use ($request) {
                        $sq->where('supplier_name', 'like', "%{$request->search}%");
                    });
            });
        }

        $accountsPayable = $query->paginate(10)
            ->withQueryString();

        return Inertia::render('purchases/AccountsPayable/Index', [
            'accountsPayable' => $accountsPayable,
            'filters' => $request->only(['status', 'search']),
        ]);
    }
}
