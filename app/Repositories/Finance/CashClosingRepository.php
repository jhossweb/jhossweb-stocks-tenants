<?php

namespace App\Repositories\Finance;

use App\Enum\TypeCoin;
use App\Models\AccountsReceivable;
use App\Models\BankTransaction;
use App\Models\CashClosing;
use App\Models\SaleOrder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashClosingRepository
{
    public function getCashSalesTotal(string $date): float
    {
        return (float) SaleOrder::query()
            ->where('sls_payment_condition', 'cash')
            ->where('sls_trans_type_code', 'FACV')
            ->where('sls_status', '!=', 'cancelled')
            ->whereNull('voided_at')
            ->whereDate('sls_date', '=', $date)
            ->sum('sls_total');
    }

    public function getReceivedTotal(string $date): float
    {
        $result = DB::table('receipts')
            ->join('bank_transactions', 'bank_transactions.id', '=', 'receipts.rec_bank_transaction_id')
            ->whereDate('bank_transactions.btr_transaction_date', '=', $date)
            ->where('receipts.rec_type', 'RECEIPT')
            ->whereNull('receipts.voided_at')
            ->sum('receipts.rec_amount');

        return (float) ($result ?? 0);
    }

    /**
     * @return array<string, float>
     */
    public function getReceivedByMethod(string $date): array
    {
        $rows = DB::table('receipts')
            ->join('bank_transactions', 'bank_transactions.id', '=', 'receipts.rec_bank_transaction_id')
            ->whereDate('bank_transactions.btr_transaction_date', '=', $date)
            ->where('receipts.rec_type', 'RECEIPT')
            ->whereNull('receipts.voided_at')
            ->groupBy('bank_transactions.btr_method')
            ->selectRaw('bank_transactions.btr_method, SUM(receipts.rec_amount) as total')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row->btr_method] = (float) $row->total;
        }

        return $result;
    }

    /**
     * @return array<int, array{coin: string, amount: float, method: string, reference: string, payment_date: string, document: string, customer: string, bank: string}>
     */
    public function getReceivedDetails(string $date): array
    {
        $rows = DB::table('receipts')
            ->join('bank_transactions', 'bank_transactions.id', '=', 'receipts.rec_bank_transaction_id')
            ->leftJoin('accounts_receivable', 'accounts_receivable.id', '=', 'receipts.rec_account_receivable_id')
            ->leftJoin('sale_orders', 'sale_orders.sls_number', '=', 'accounts_receivable.acr_sls_number')
            ->leftJoin('customers', 'customers.id', '=', 'sale_orders.sls_customer_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bank_transactions.btr_bank_account_id')
            ->whereDate('bank_transactions.btr_transaction_date', '=', $date)
            ->where('receipts.rec_type', 'RECEIPT')
            ->whereNull('receipts.voided_at')
            ->orderBy('bank_transactions.btr_coin')
            ->orderBy('bank_transactions.btr_method')
            ->orderBy('bank_transactions.id')
            ->selectRaw("
                bank_transactions.btr_coin,
                bank_transactions.btr_amount as original_amount,
                bank_transactions.btr_method,
                bank_transactions.btr_reference,
                bank_transactions.btr_transaction_date,
                coalesce(sale_orders.sls_number, bank_transactions.btr_reference, 'S/N') as document_number,
                coalesce(customers.clt_name, '') as customer_name,
                coalesce(bank_accounts.bank_name, '') as bank_name
            ")
            ->get();

        return $rows->map(fn ($row) => [
            'coin' => $row->btr_coin instanceof TypeCoin
                ? $row->btr_coin->value
                : (string) $row->btr_coin,
            'amount' => (float) $row->original_amount,
            'method' => (string) $row->btr_method,
            'reference' => (string) ($row->btr_reference ?? ''),
            'payment_date' => (string) $row->btr_transaction_date,
            'document' => (string) $row->document_number,
            'customer' => (string) $row->customer_name,
            'bank' => (string) $row->bank_name,
        ])->values()->all();
    }

    /**
     * @return array<string, array<string, float>> coin => method => total
     */
    public function getGroupedReceipts(string $date): array
    {
        $rows = DB::table('receipts')
            ->join('bank_transactions', 'bank_transactions.id', '=', 'receipts.rec_bank_transaction_id')
            ->whereDate('bank_transactions.btr_transaction_date', '=', $date)
            ->where('receipts.rec_type', 'RECEIPT')
            ->whereNull('receipts.voided_at')
            ->groupBy('bank_transactions.btr_coin', 'bank_transactions.btr_method')
            ->selectRaw('
                bank_transactions.btr_coin,
                bank_transactions.btr_method,
                SUM(bank_transactions.btr_amount) as total
            ')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $coin = $row->btr_coin instanceof TypeCoin
                ? $row->btr_coin->value
                : (string) $row->btr_coin;
            $method = (string) $row->btr_method;
            $result[$coin][$method] = (float) $row->total;
        }

        return $result;
    }

    public function getVueltoTotal(string $date): float
    {
        $result = DB::table('bank_transactions')
            ->whereDate('btr_transaction_date', '=', $date)
            ->where('btr_type', '=', BankTransaction::TYPE_OUTFLOW)
            ->where('btr_description', '=', BankTransaction::VUELTO_DESCRIPTION)
            ->sum('btr_amount');

        return (float) ($result ?? 0);
    }

    /**
     * @return array<int, array{coin: string, amount: float, method: string, reference: string, payment_date: string, document: string, customer: string, bank: string}>
     */
    public function getVueltoDetails(string $date): array
    {
        $rows = DB::table('bank_transactions')
            ->leftJoin('accounts_receivable', function ($join) {
                $join->on('accounts_receivable.id', '=', 'bank_transactions.btr_transactionable_id')
                    ->where('bank_transactions.btr_transactionable_type', '=', AccountsReceivable::class);
            })
            ->leftJoin('sale_orders', 'sale_orders.sls_number', '=', 'accounts_receivable.acr_sls_number')
            ->leftJoin('customers', 'customers.id', '=', 'sale_orders.sls_customer_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bank_transactions.btr_bank_account_id')
            ->whereDate('bank_transactions.btr_transaction_date', '=', $date)
            ->where('bank_transactions.btr_type', '=', BankTransaction::TYPE_OUTFLOW)
            ->where('bank_transactions.btr_description', '=', BankTransaction::VUELTO_DESCRIPTION)
            ->orderBy('bank_transactions.btr_coin')
            ->orderBy('bank_transactions.btr_method')
            ->orderBy('bank_transactions.id')
            ->selectRaw("
                bank_transactions.btr_coin,
                bank_transactions.btr_amount as original_amount,
                bank_transactions.btr_method,
                bank_transactions.btr_reference,
                bank_transactions.btr_transaction_date,
                coalesce(sale_orders.sls_number, bank_transactions.btr_reference, 'S/N') as document_number,
                coalesce(customers.clt_name, '') as customer_name,
                coalesce(bank_accounts.bank_name, '') as bank_name
            ")
            ->get();

        return $rows->map(fn ($row) => [
            'coin' => $row->btr_coin instanceof TypeCoin
                ? $row->btr_coin->value
                : (string) $row->btr_coin,
            'amount' => (float) $row->original_amount,
            'method' => (string) $row->btr_method,
            'reference' => (string) ($row->btr_reference ?? ''),
            'payment_date' => (string) $row->btr_transaction_date,
            'document' => (string) $row->document_number,
            'customer' => (string) $row->customer_name,
            'bank' => (string) $row->bank_name,
        ])->values()->all();
    }

    /**
     * @return array<string, array<string, float>> coin => method => total
     */
    public function getGroupedVueltos(string $date): array
    {
        $rows = DB::table('bank_transactions')
            ->whereDate('btr_transaction_date', '=', $date)
            ->where('btr_type', '=', BankTransaction::TYPE_OUTFLOW)
            ->where('btr_description', '=', BankTransaction::VUELTO_DESCRIPTION)
            ->groupBy('btr_coin', 'btr_method')
            ->selectRaw('
                btr_coin,
                btr_method,
                SUM(btr_amount) as total
            ')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $coin = $row->btr_coin instanceof TypeCoin
                ? $row->btr_coin->value
                : (string) $row->btr_coin;
            $method = (string) $row->btr_method;
            $result[$coin][$method] = (float) $row->total;
        }

        return $result;
    }

    public function findClosingByDate(string $date): ?CashClosing
    {
        return CashClosing::query()
            ->with(['movements', 'closedBy:id,name'])
            ->where('cc_date', $date)
            ->first();
    }

    public function storeClosing(array $data, Collection $movements): CashClosing
    {
        return DB::transaction(function () use ($data, $movements) {
            $closing = CashClosing::create($data);

            if ($movements->isNotEmpty()) {
                $closing->movements()->createMany($movements->toArray());
            }

            return $closing;
        });
    }

    public function getHistory(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null,
    ): LengthAwarePaginator {
        $query = CashClosing::query()
            ->select([
                'id',
                'cc_date',
                'cc_opening_balance',
                'cc_expected_closing',
                'cc_counted_amount',
                'cc_difference',
                'cc_status',
                'cc_notes',
                'cc_closed_by',
                'cc_closed_at',
                'cc_counted_details',
                'cc_differences',
            ])
            ->with('closedBy:id,name');

        if ($dateFrom !== null) {
            $query->where('cc_date', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->where('cc_date', '<=', $dateTo);
        }

        if ($status !== null) {
            $query->where('cc_status', $status);
        }

        return $query->orderByDesc('cc_date')->paginate(15);
    }
}
