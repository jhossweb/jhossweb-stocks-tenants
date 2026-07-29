<?php

namespace App\Repositories\Finance\Cobranza;

use App\Models\AccountsReceivable;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

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
}
