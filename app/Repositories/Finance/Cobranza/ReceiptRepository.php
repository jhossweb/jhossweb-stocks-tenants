<?php

namespace App\Repositories\Finance\Cobranza;

use App\Models\AccountsReceivable;
use App\Models\Customer;
use App\Models\Receipt;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     * Paginated cobros history grouped by receivable (document).
     * Shows total amount in USD. Detail shows all movements.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function history(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Receipt::with([
            'accountsReceivable.saleOrder.customer',
            'bankTransaction.bankAccount',
        ])->latest('receipts.created_at');

        if ($search) {
            $query->whereHas('accountsReceivable.saleOrder', function ($q) use ($search) {
                $q->where('sls_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($sq) use ($search) {
                        $sq->where('clt_name', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $receipts = $paginator->getCollection();

        // Group by accounts receivable (same document/payment group)
        $groups = $receipts
            ->groupBy('rec_account_receivable_id')
            ->map(function (Collection $groupReceipts) {
                $first = $groupReceipts->first();
                $accountsReceivable = $first->accountsReceivable;
                $saleOrder = $accountsReceivable?->saleOrder;
                $customer = $saleOrder?->customer;

                $totalUsd = 0.0;
                foreach ($groupReceipts as $receipt) {
                    $bankTransaction = $receipt->bankTransaction;
                    if ($bankTransaction) {
                        $amount = (float) ($bankTransaction->btr_amount ?? 0);
                        $coin = strtoupper((string) ($bankTransaction->btr_coin?->value ?? $bankTransaction->btr_coin ?? 'USD'));
                        if ($coin === 'USD') {
                            $totalUsd += $amount;
                        } else {
                            // Convert to USD using valorcam/diferencial logic
                            // btr_valorcam is rate used; approximate conversion to USD
                            // Try to use existing conversion pattern
                            $valorcam = (float) ($bankTransaction->btr_valorcam ?? 0);
                            if ($valorcam > 0) {
                                $totalUsd += $amount / $valorcam;
                            } else {
                                $totalUsd += (float) ($receipt->rec_amount ?? 0);
                            }
                        }
                    } else {
                        // No bank transaction, try rec_amount in USD context
                        $totalUsd += (float) ($receipt->rec_amount ?? 0);
                    }
                }

                // Get latest date
                $latestDate = $groupReceipts->max(function ($r) {
                    $bt = $r->bankTransaction;

                    return $bt?->btr_transaction_date ?? $r->created_at;
                });

                // Get banks/references summary
                $banks = $groupReceipts->map(function ($r) {
                    return $r->bankTransaction?->bankAccount?->bank_name;
                })->filter()->unique()->values()->all();

                $references = $groupReceipts->map(function ($r) {
                    return $r->bankTransaction?->btr_reference;
                })->filter()->values()->all();

                return [
                    'id' => $first->id, // representative receipt id for detail link
                    'group_key' => $first->rec_account_receivable_id,
                    'rec_account_receivable_id' => $first->rec_account_receivable_id,
                    'rec_type' => $first->rec_type,
                    'created_at' => $latestDate,
                    'total_usd' => round($totalUsd, 4),
                    'movements_count' => $groupReceipts->count(),
                    'banks' => $banks,
                    'references' => $references,
                    'accounts_receivable' => $accountsReceivable ? [
                        'id' => $accountsReceivable->id,
                        'acr_sls_number' => $accountsReceivable->acr_sls_number,
                        'acr_total' => $accountsReceivable->acr_total,
                        'acr_balance' => $accountsReceivable->acr_balance,
                        'acr_status' => $accountsReceivable->acr_status,
                        'sale_order' => $saleOrder ? [
                            'id' => $saleOrder->id,
                            'sls_number' => $saleOrder->sls_number,
                            'sls_coin' => $saleOrder->sls_coin,
                            'sls_total' => $saleOrder->sls_total,
                            'sls_date' => $saleOrder->sls_date,
                            'customer' => $customer ? [
                                'id' => $customer->id,
                                'clt_name' => $customer->clt_name,
                                'clt_cedula' => $customer->clt_cedula,
                            ] : null,
                        ] : null,
                    ] : null,
                    'receipts' => $groupReceipts->values()->all(), // all movements for detail
                ];
            })
            ->values();

        return new LengthAwarePaginator(
            $groups,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => $paginator->path(),
                'pageName' => $paginator->getPageName(),
            ]
        );
    }
}
