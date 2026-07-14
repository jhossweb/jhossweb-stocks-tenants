<?php

use App\Models\Rate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // --- Accounts Receivable ---
        $acrs = DB::table('accounts_receivable')
            ->join('sale_orders', 'accounts_receivable.acr_sls_number', '=', 'sale_orders.sls_number')
            ->select('accounts_receivable.id', 'accounts_receivable.acr_total', 'sale_orders.sls_coin', 'sale_orders.sls_valorcam')
            ->whereNull('accounts_receivable.acr_total_usd')
            ->get();

        foreach ($acrs as $acr) {
            $totalUsd = $this->convertToUsd($acr->acr_total, $acr->sls_coin, $acr->sls_valorcam);
            DB::table('accounts_receivable')
                ->where('id', $acr->id)
                ->update([
                    'acr_total_usd' => $totalUsd,
                    'acr_balance_usd' => $totalUsd,
                ]);
        }

        // Adjust balances for existing payments
        $paidAcrs = DB::table('accounts_receivable')
            ->join('sale_orders', 'accounts_receivable.acr_sls_number', '=', 'sale_orders.sls_number')
            ->join('receipts', 'receipts.rec_account_receivable_id', '=', 'accounts_receivable.id')
            ->join('bank_transactions', 'receipts.rec_bank_transaction_id', '=', 'bank_transactions.id')
            ->select(
                'accounts_receivable.id',
                'accounts_receivable.acr_balance_usd',
                'receipts.rec_amount',
                'bank_transactions.btr_coin',
                'bank_transactions.btr_valorcam',
                'bank_transactions.btr_transaction_date',
                'sale_orders.sls_coin'
            )
            ->whereNotNull('accounts_receivable.acr_total_usd')
            ->where('accounts_receivable.acr_balance', '>', 0)
            ->get();

        foreach ($paidAcrs as $paid) {
            $paymentUsd = $this->convertPaymentToUsd(
                $paid->rec_amount,
                $paid->btr_coin,
                $paid->btr_valorcam,
                $paid->btr_transaction_date
            );
            DB::table('accounts_receivable')
                ->where('id', $paid->id)
                ->decrement('acr_balance_usd', $paymentUsd);
        }

        // --- Accounts Payable ---
        $acps = DB::table('accounts_payables')
            ->join('purchase_orders', 'accounts_payables.acp_purchase_number', '=', 'purchase_orders.invoice_number')
            ->select('accounts_payables.id', 'accounts_payables.acp_total', 'purchase_orders.invoice_coin', 'purchase_orders.invoice_valorcam')
            ->whereNull('accounts_payables.acp_total_usd')
            ->get();

        foreach ($acps as $acp) {
            $totalUsd = $this->convertToUsd($acp->acp_total, $acp->invoice_coin, $acp->invoice_valorcam);
            DB::table('accounts_payables')
                ->where('id', $acp->id)
                ->update([
                    'acp_total_usd' => $totalUsd,
                    'acp_balance_usd' => $totalUsd,
                ]);
        }

        // Adjust balances for existing payments
        $paidAcps = DB::table('accounts_payables')
            ->join('purchase_orders', 'accounts_payables.acp_purchase_number', '=', 'purchase_orders.invoice_number')
            ->join('account_payments', 'account_payments.apy_account_payable_id', '=', 'accounts_payables.id')
            ->join('bank_transactions', 'account_payments.apy_bank_transaction_id', '=', 'bank_transactions.id')
            ->select(
                'accounts_payables.id',
                'accounts_payables.acp_balance_usd',
                'account_payments.apy_amount',
                'bank_transactions.btr_coin',
                'bank_transactions.btr_valorcam',
                'bank_transactions.btr_transaction_date',
                'purchase_orders.invoice_coin'
            )
            ->whereNotNull('accounts_payables.acp_total_usd')
            ->where('accounts_payables.acp_balance', '>', 0)
            ->get();

        foreach ($paidAcps as $paid) {
            $paymentUsd = $this->convertPaymentToUsd(
                $paid->apy_amount,
                $paid->btr_coin,
                $paid->btr_valorcam,
                $paid->btr_transaction_date
            );
            DB::table('accounts_payables')
                ->where('id', $paid->id)
                ->decrement('acp_balance_usd', $paymentUsd);
        }

        // --- Bank Transactions: calculate diferencial for existing receipts ---
        $receipts = DB::table('receipts')
            ->join('bank_transactions', 'receipts.rec_bank_transaction_id', '=', 'bank_transactions.id')
            ->join('accounts_receivable', 'receipts.rec_account_receivable_id', '=', 'accounts_receivable.id')
            ->join('sale_orders', 'accounts_receivable.acr_sls_number', '=', 'sale_orders.sls_number')
            ->select(
                'receipts.id as rec_id',
                'receipts.rec_amount',
                'bank_transactions.id as btr_id',
                'bank_transactions.btr_coin',
                'bank_transactions.btr_valorcam',
                'bank_transactions.btr_transaction_date',
                'sale_orders.sls_valorcam as doc_valorcam',
                'sale_orders.sls_coin as doc_coin'
            )
            ->where('bank_transactions.btr_diferencial', 0)
            ->where('receipts.rec_diferencial', 0)
            ->where('receipts.rec_amount', '>', 0)
            ->get();

        foreach ($receipts as $rec) {
            $diferencial = $this->calculateDiferencial(
                $rec->rec_amount,
                $rec->btr_coin,
                $rec->btr_valorcam,
                $rec->doc_valorcam,
                $rec->doc_coin,
                $rec->btr_transaction_date
            );

            if (abs($diferencial) > 0.001) {
                DB::table('bank_transactions')
                    ->where('id', $rec->btr_id)
                    ->update(['btr_diferencial' => $diferencial]);

                DB::table('receipts')
                    ->where('id', $rec->rec_id)
                    ->update(['rec_diferencial' => $diferencial]);
            }
        }

        // --- Bank Transactions: calculate diferencial for existing account_payments ---
        $payments = DB::table('account_payments')
            ->join('bank_transactions', 'account_payments.apy_bank_transaction_id', '=', 'bank_transactions.id')
            ->join('accounts_payables', 'account_payments.apy_account_payable_id', '=', 'accounts_payables.id')
            ->join('purchase_orders', 'accounts_payables.acp_purchase_number', '=', 'purchase_orders.invoice_number')
            ->select(
                'account_payments.id as apy_id',
                'account_payments.apy_amount',
                'bank_transactions.id as btr_id',
                'bank_transactions.btr_coin',
                'bank_transactions.btr_valorcam',
                'bank_transactions.btr_transaction_date',
                'purchase_orders.invoice_valorcam as doc_valorcam',
                'purchase_orders.invoice_coin as doc_coin'
            )
            ->where('bank_transactions.btr_diferencial', 0)
            ->where('account_payments.apy_diferencial', 0)
            ->where('account_payments.apy_amount', '>', 0)
            ->get();

        foreach ($payments as $pay) {
            $diferencial = $this->calculateDiferencial(
                $pay->apy_amount,
                $pay->btr_coin,
                $pay->btr_valorcam,
                $pay->doc_valorcam,
                $pay->doc_coin,
                $pay->btr_transaction_date
            );

            if (abs($diferencial) > 0.001) {
                DB::table('bank_transactions')
                    ->where('id', $pay->btr_id)
                    ->update(['btr_diferencial' => $diferencial]);

                DB::table('account_payments')
                    ->where('id', $pay->apy_id)
                    ->update(['apy_diferencial' => $diferencial]);
            }
        }
    }

    public function down(): void
    {
        DB::table('accounts_receivable')->update(['acr_total_usd' => null, 'acr_balance_usd' => null]);
        DB::table('accounts_payables')->update(['acp_total_usd' => null, 'acp_balance_usd' => null]);
        DB::table('bank_transactions')->update(['btr_diferencial' => 0]);
        DB::table('receipts')->update(['rec_diferencial' => 0]);
        DB::table('account_payments')->update(['apy_diferencial' => 0]);
    }

    private function convertToUsd(float $amount, string $coin, float $valorcam): float
    {
        return match (strtoupper($coin)) {
            'USD' => $amount,
            'COP' => $valorcam > 0 ? $amount / $valorcam : $amount,
            'EUR' => $valorcam > 0 ? $amount / $valorcam : $amount,
            default => $valorcam > 0 ? $amount / $valorcam : $amount,
        };
    }

    private function convertPaymentToUsd(float $amount, ?string $payCoin, float $payValorcam, mixed $transactionDate): float
    {
        if (! $payCoin) {
            return $this->convertToUsd($amount, 'USD', $payValorcam);
        }

        $rate = is_string($transactionDate)
            ? Rate::where('rate_date', $transactionDate)->first()
            : $transactionDate;

        if (! $rate) {
            return $this->convertToUsd($amount, $payCoin, $payValorcam);
        }

        return match (strtoupper($payCoin)) {
            'USD' => $amount,
            'COP' => (float) $rate->rate_cop > 0 ? $amount / (float) $rate->rate_cop : $amount,
            'EUR' => (float) $rate->rate_eur > 0 ? $amount / (float) $rate->rate_eur : $amount,
            default => (float) $rate->rate_usd > 0 ? $amount / (float) $rate->rate_usd : $amount,
        };
    }

    private function calculateDiferencial(
        float $paymentAmount,
        ?string $payCoin,
        float $payValorcam,
        float $docValorcam,
        ?string $docCoin,
        mixed $transactionDate,
    ): float {
        if (! $payCoin || strtoupper($payCoin) === 'USD') {
            return 0.0;
        }

        $amountAtDocRate = $this->convertToUsd($paymentAmount, $payCoin, $docValorcam);
        $amountAtPayRate = $this->convertPaymentToUsd($paymentAmount, $payCoin, $payValorcam, $transactionDate);

        return round($amountAtDocRate - $amountAtPayRate, 4);
    }
};
