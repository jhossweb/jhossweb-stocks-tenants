<?php

namespace App\Repositories\Sales\AccountsReceivable;

use App\Models\AccountsReceivable;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountsReceivableRepository
{
    public function creditPending(?string $search = null, ?string $due = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->baseCreditQuery();

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('accounts_receivable.acr_sls_number', 'like', "%{$search}%")
                    ->orWhereHas('saleOrder.customer', function (Builder $sq) use ($search) {
                        $sq->where('clt_name', 'like', "%{$search}%")
                            ->orWhere('clt_code', 'like', "%{$search}%")
                            ->orWhere('clt_cedula', 'like', "%{$search}%");
                    });
            });
        }

        if ($due === 'overdue') {
            $query->whereDate('sale_orders.sls_due_date', '<', today());
        } elseif ($due === 'upcoming') {
            $query->whereDate('sale_orders.sls_due_date', '>=', today());
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getClientCreditPending(Customer $customer): Collection
    {
        return $this->baseCreditQuery()
            ->where('sale_orders.sls_customer_id', $customer->id)
            ->get();
    }

    private function baseCreditQuery(): Builder
    {
        return AccountsReceivable::with(['saleOrder.customer', 'saleOrder.transactionType', 'payments.bankTransaction'])
            ->join('sale_orders', 'sale_orders.sls_number', '=', 'accounts_receivable.acr_sls_number')
            ->select('accounts_receivable.*')
            ->whereIn('accounts_receivable.acr_status', ['pending', 'partial'])
            ->where('sale_orders.sls_trans_type_code', 'FACV')
            ->orderBy('sale_orders.sls_due_date');
    }
}
