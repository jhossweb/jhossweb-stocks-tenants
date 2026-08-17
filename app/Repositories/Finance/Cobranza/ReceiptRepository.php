<?php

namespace App\Repositories\Finance\Cobranza;

use App\Models\AccountsReceivable;
use App\Models\Customer;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ReceiptRepository
{
    /**
     * @return Collection<int, AccountsReceivable>
     */
    public function getPendingReceivables(Customer $customer): Collection
    {
        return AccountsReceivable::with('saleOrder')
            ->whereHas('saleOrder', function ($query) use ($customer) {
                $query->where('sls_customer_id', $customer->id);
            })
            ->where('acr_balance', '>', 0)
            ->get();
    }

    /**
     * Paginated cobros history with document, customer and bank context.
     */
    public function history(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Receipt::with([
            'accountsReceivable.saleOrder.customer',
            'bankTransaction.bankAccount',
        ])->latest();

        if ($search) {
            $query->whereHas('accountsReceivable.saleOrder', function ($q) use ($search) {
                $q->where('sls_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($sq) use ($search) {
                        $sq->where('clt_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
