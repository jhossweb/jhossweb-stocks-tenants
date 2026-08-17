<?php

namespace App\Repositories\Purchases;

use App\Models\AccountPayments;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountPaymentRepository
{
    /**
     * Paginated payments history with document, supplier and bank context.
     */
    public function history(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = AccountPayments::with([
            'accountPayable.purchaseOrder.supplier',
            'bankTransaction.bankAccount',
        ])->latest();

        if ($search) {
            $query->whereHas('accountPayable.purchaseOrder', function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('supplier_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
