<?php

namespace App\Services\Finance\Cobranza;

use App\Enum\Finanzas\TypeReceipt;
use App\Enum\Sales\StatusSales;
use App\Models\AccountsReceivable;
use App\Models\BankTransaction;
use App\Models\Rate;
use App\Models\Receipt;
use App\Repositories\Finance\Cobranza\ReceiptRepository;
use App\Services\Finance\RateService;
use Illuminate\Support\Facades\DB;

class ReceiptService
{
    public function __construct(
        protected RateService $rateService,
        protected ReceiptRepository $repo,
    ) {}

    public function processPayments(array $validated): void
    {
        $payRate = $this->rateService->getExactRateForDate($validated['payment_date']);

        if (! $payRate) {
            throw new \RuntimeException('No existe tasa de cambio para la fecha seleccionada.');
        }

        $activePayments = array_filter($validated['payments'], fn ($p) => (float) $p['amount'] > 0);

        if (empty($activePayments)) {
            throw new \RuntimeException('Debe ingresar al menos un monto a cobrar.');
        }

        $valorcam = $this->rateService->resolveRateForCoin(
            $validated['payment_date'],
            $validated['payment_currency'],
        );

        DB::transaction(function () use ($activePayments, $validated, $payRate, $valorcam) {
            foreach ($activePayments as $paymentData) {
                $acr = AccountsReceivable::with('saleOrder')->findOrFail($paymentData['acr_id']);

                $paymentAmount = (float) $paymentData['amount'];
                $payCurrency = $validated['payment_currency'];
                $docCurrency = $acr->saleOrder->sls_coin ?? 'USD';

                $docDate = $acr->saleOrder->sls_date?->format('Y-m-d')
                    ?? $acr->saleOrder->created_at?->format('Y-m-d');
                $docRate = $docDate
                    ? ($this->rateService->getExactRateForDate($docDate) ?? $payRate)
                    : $payRate;

                $rate = $this->rateService->getExactRateForDate($validated['payment_date']);
                $amountInDocCurrency = Rate::convert($paymentAmount, $payCurrency, $docCurrency, $rate);

                $diferencial = $this->rateService->calculateDiferencial(
                    $paymentAmount,
                    $payCurrency,
                    $payRate,
                    $docRate,
                );

                $amountUsd = Rate::convert($paymentAmount, $payCurrency, 'USD', $payRate);

                $bankTransaction = BankTransaction::create([
                    'btr_bank_account_id' => $validated['bank_account_id'],
                    'btr_type' => 'inflow',
                    'btr_amount' => $paymentAmount,
                    'btr_reference' => $validated['payment_reference'],
                    'btr_description' => $validated['payment_description'] ?: 'Cobro a cliente',
                    'btr_valorcam' => $valorcam,
                    'btr_diferencial' => $diferencial,
                    'btr_balance' => 0,
                    'btr_method' => $validated['payment_method'],
                    'btr_transaction_date' => $validated['payment_date'],
                    'btr_coin' => $validated['payment_currency'],
                ]);

                $bankTransaction->btr_transactionable()->associate($acr);
                $bankTransaction->save();

                $acr->registerPayment(
                    $amountInDocCurrency,
                    TypeReceipt::RECEIPT->value,
                    $bankTransaction->id,
                    $amountUsd,
                );

                $receipt = $acr->payments()
                    ->where('rec_bank_transaction_id', $bankTransaction->id)
                    ->first();

                if ($receipt) {
                    $receipt->rec_diferencial = $diferencial;
                    $receipt->save();
                }
            }
        });
    }

    public function updatePayment(Receipt $payment, array $validated): void
    {
        $acr = $payment->accountsReceivable;

        $oldAmount = $payment->rec_amount;
        $acr->acr_balance += $oldAmount;

        $newAmount = (float) $validated['amount'];
        if ($newAmount > $acr->acr_balance) {
            throw new \RuntimeException('El monto no puede ser mayor a la deuda pendiente de la factura ($'.number_format($acr->acr_balance, 2).').');
        }

        DB::transaction(function () use ($payment, $acr, $newAmount, $oldAmount, $validated) {
            $acr->acr_balance -= $newAmount;

            if ($acr->acr_balance <= 0) {
                $acr->acr_status = StatusSales::PAID->value;
                $acr->acr_balance = 0;
            } elseif ($acr->acr_balance == $acr->acr_total) {
                $acr->acr_status = StatusSales::PENDING->value;
            } else {
                $acr->acr_status = StatusSales::PARTIAL->value;
            }
            $acr->save();

            if ($acr->saleOrder) {
                $acr->saleOrder->sls_status = $acr->acr_status;
                $acr->saleOrder->save();
            }

            $bankTx = $payment->bankTransaction;
            if ($bankTx) {
                $bankTx->btr_bank_account_id = (int) $validated['bank_account_id'];
                $bankTx->btr_transaction_date = $validated['payment_date'];
                $bankTx->btr_reference = $validated['payment_reference'];
                $bankTx->btr_description = $validated['payment_description'];
                $bankTx->btr_amount = ($bankTx->btr_amount - $oldAmount) + $newAmount;
                $bankTx->btr_coin = $validated['payment_currency'];
                $bankTx->btr_valorcam = $this->rateService->resolveRateForCoin(
                    $validated['payment_date'],
                    $validated['payment_currency'],
                );
                $bankTx->save();
            }

            $payment->rec_amount = $newAmount;
            $payment->save();
        });
    }

    public function deletePayment(Receipt $payment): void
    {
        $amount = $payment->rec_amount;

        DB::transaction(function () use ($payment, $amount) {
            $acr = $payment->accountsReceivable;
            $acr->acr_balance += $amount;

            if ($acr->acr_balance_usd !== null && $payment->bankTransaction) {
                $payCoin = $payment->bankTransaction->btr_coin?->value ?? $payment->bankTransaction->btr_coin;
                if ($payCoin && strtoupper((string) $payCoin) !== 'USD' && $payment->bankTransaction->btr_valorcam > 0) {
                    $paymentUsd = $amount / (float) $payment->bankTransaction->btr_valorcam;
                } else {
                    $paymentUsd = $amount;
                }
                $acr->acr_balance_usd += $paymentUsd;
            }

            if ($acr->acr_balance >= $acr->acr_total) {
                $acr->acr_status = StatusSales::PENDING->value;
            } elseif ($acr->acr_balance > 0) {
                $acr->acr_status = StatusSales::PARTIAL->value;
            }
            $acr->save();

            if ($acr->saleOrder) {
                $acr->saleOrder->sls_status = $acr->acr_status;
                $acr->saleOrder->save();
            }

            $bankTx = $payment->bankTransaction;
            if ($bankTx) {
                $bankTx->btr_amount -= $amount;
                if ($bankTx->btr_amount <= 0) {
                    $bankTx->delete();
                } else {
                    $bankTx->btr_diferencial = 0;
                    $bankTx->save();
                }
            }

            $payment->delete();
        });
    }
}
