<?php

namespace App\Services\Purchases;

use App\Enum\Finanzas\TypePayment;
use App\Models\AccountsPayable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Rate;
use App\Services\Dashboard\DashboardCacheInvalidator;
use App\Services\Finance\RateService;
use Illuminate\Support\Facades\DB;

class PurchasePaymentService
{
    public function __construct(
        protected RateService $rateService,
    ) {}

    /**
     * Register one or more payments against supplier invoices.
     *
     * @param  array<string, mixed>  $validated  Expects payment_date, bank_account_id,
     *                                           payment_currency, payment_rate, payment_method,
     *                                           payment_reference, payment_description and
     *                                           payments[][acp_id|amount|optional per-payment fields].
     */
    public function pay(array $validated): void
    {
        $activePayments = array_values(array_filter(
            $validated['payments'],
            fn ($p) => (float) $p['amount'] > 0
        ));

        if (empty($activePayments)) {
            throw new \RuntimeException('Debe ingresar al menos un monto a pagar.');
        }

        foreach ($activePayments as $paymentData) {
            $bankId = $paymentData['bank_account_id'] ?? $validated['bank_account_id'];
            $bankAccount = BankAccount::findOrFail($bankId);

            if ($bankAccount->bank_balance < (float) $paymentData['amount']) {
                throw new \RuntimeException(
                    'Saldo insuficiente. Saldo disponible en '.$bankAccount->bank_name.': '
                    .number_format((float) $bankAccount->bank_balance, 4).' '.$bankAccount->bank_currency
                );
            }
        }

        DB::transaction(function () use ($activePayments, $validated) {
            foreach ($activePayments as $paymentData) {
                $payDate = $paymentData['payment_date'] ?? $validated['payment_date'];
                $payCurrency = $paymentData['payment_currency'] ?? $validated['payment_currency'];
                $bankId = $paymentData['bank_account_id'] ?? $validated['bank_account_id'];
                $payMethod = $paymentData['payment_method'] ?? $validated['payment_method'];
                $payReference = $paymentData['payment_reference'] ?? $validated['payment_reference'];
                $payDescription = $paymentData['payment_description']
                    ?? $validated['payment_description']
                    ?? null;

                $payRate = $this->rateService->getBestRateForDate($payDate);

                if (! $payRate) {
                    throw new \RuntimeException('No existe tasa de cambio para la fecha seleccionada.');
                }

                $acp = AccountsPayable::with('purchaseOrder')->findOrFail($paymentData['acp_id']);

                $paymentAmount = (float) $paymentData['amount'];
                $docCurrency = $acp->purchaseOrder->invoice_coin ?? 'USD';

                // Get the document's rate record for differential calculation
                $docDate = $acp->purchaseOrder->invoice_date?->format('Y-m-d')
                    ?? $acp->purchaseOrder->created_at?->format('Y-m-d');
                $docRate = $docDate
                    ? ($this->rateService->getExactRateForDate($docDate) ?? $payRate)
                    : $payRate;

                // Convert payment to document currency (existing behavior)
                $amountInDocCurrency = round(Rate::convert($paymentAmount, $payCurrency, $docCurrency, $payRate), 4);

                // Calculate differential in USD
                $diferencial = $this->rateService->calculateDiferencial(
                    $paymentAmount,
                    $payCurrency,
                    $payRate,
                    $docRate,
                );

                // Convert payment to USD for balance tracking
                $amountUsd = round(Rate::convert($paymentAmount, $payCurrency, 'USD', $payRate), 4);

                $valorcam = $this->rateService->resolveRateForCoin($payDate, $payCurrency);

                $bankTransaction = BankTransaction::create([
                    'btr_bank_account_id' => $bankId,
                    'btr_type' => 'outflow',
                    'btr_amount' => round($paymentAmount, 4),
                    'btr_reference' => $payReference,
                    'btr_description' => $payDescription ?: 'Pago a proveedor',
                    'btr_valorcam' => $valorcam,
                    'btr_diferencial' => $diferencial,
                    'btr_balance' => 0,
                    'btr_method' => $payMethod,
                    'btr_transaction_date' => $payDate,
                    'btr_coin' => $payCurrency,
                ]);

                $bankTransaction->btr_transactionable()->associate($acp);
                $bankTransaction->save();

                $acp->registerPayment(
                    $amountInDocCurrency,
                    TypePayment::PAYMENT->value,
                    $bankTransaction->id,
                    null,
                    $amountUsd,
                );

                // Store differential on account payment
                $accountPayment = $acp->payments()->where('apy_bank_transaction_id', $bankTransaction->id)->first();
                if ($accountPayment) {
                    $accountPayment->apy_diferencial = $diferencial;
                    $accountPayment->save();
                }
            }
        });

        DashboardCacheInvalidator::payables(substr($validated['payment_date'], 0, 7));
    }
}
