<?php

namespace App\Services\Finance\Cobranza;

use App\Enum\Finanzas\TypeReceipt;
use App\Enum\Sales\StatusSales;
use App\Models\AccountsReceivable;
use App\Models\BankTransaction;
use App\Models\Rate;
use App\Models\Receipt;
use App\Repositories\Finance\Cobranza\ReceiptRepository;
use App\Services\Dashboard\DashboardCacheInvalidator;
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
        $activePayments = array_values(array_filter(
            $validated['payments'],
            fn ($p) => (float) $p['amount'] > 0
        ));

        if (empty($activePayments)) {
            throw new \RuntimeException('Debe ingresar al menos un monto a cobrar.');
        }

        DB::transaction(function () use ($activePayments, $validated) {
            $applyVuelto = (bool) ($validated['apply_vuelto'] ?? false);
            $remainingBalance = null;
            $excessInDoc = 0.0;
            $lastActivePayment = null;
            $lastDocCurrency = 'USD';
            $lastPayRate = null;

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

                $acr = AccountsReceivable::with('saleOrder')->findOrFail($paymentData['acr_id']);

                if ($remainingBalance === null) {
                    $remainingBalance = (float) $acr->acr_balance;
                }

                $paymentAmount = (float) $paymentData['amount'];
                $docCurrency = $acr->saleOrder->sls_coin ?? 'USD';

                $docDate = $acr->saleOrder->sls_date?->format('Y-m-d')
                    ?? $acr->saleOrder->created_at?->format('Y-m-d');
                $docRate = $docDate
                    ? ($this->rateService->getExactRateForDate($docDate) ?? $payRate)
                    : $payRate;

                $amountInDocCurrency = round(Rate::convert($paymentAmount, $payCurrency, $docCurrency, $payRate), 4);

                $appliedInDoc = $amountInDocCurrency;
                if ($applyVuelto && $remainingBalance > 0) {
                    $appliedInDoc = min($amountInDocCurrency, $remainingBalance);
                    $excessInDoc += round($amountInDocCurrency - $appliedInDoc, 4);
                    $remainingBalance = round($remainingBalance - $appliedInDoc, 4);
                }

                $diferencial = $this->rateService->calculateDiferencial(
                    $appliedInDoc,
                    $payCurrency,
                    $payRate,
                    $docRate,
                );

                $amountUsd = round(Rate::convert($appliedInDoc, $payCurrency, 'USD', $payRate), 4);

                $valorcam = $this->rateService->resolveRateForCoin($payDate, $payCurrency);

                $bankTransaction = BankTransaction::create([
                    'btr_bank_account_id' => $bankId,
                    'btr_type' => 'inflow',
                    'btr_amount' => round($appliedInDoc, 4),
                    'btr_reference' => $payReference,
                    'btr_description' => $payDescription ?: 'Cobro a cliente',
                    'btr_valorcam' => $valorcam,
                    'btr_diferencial' => $diferencial,
                    'btr_balance' => 0,
                    'btr_method' => $payMethod,
                    'btr_transaction_date' => $payDate,
                    'btr_coin' => $payCurrency,
                ]);

                $bankTransaction->btr_transactionable()->associate($acr);
                $bankTransaction->save();

                $receipt = $acr->registerPayment(
                    $appliedInDoc,
                    TypeReceipt::RECEIPT->value,
                    $bankTransaction->id,
                    $amountUsd,
                );

                $receipt->rec_diferencial = $diferencial;
                $receipt->save();

                $lastActivePayment = $paymentData;
                $lastDocCurrency = $docCurrency;
                $lastPayRate = $payRate;
            }

            if ($applyVuelto && $lastActivePayment) {
                $vueltos = array_filter(
                    $validated['vueltos'] ?? [],
                    fn (array $v): bool => (float) ($v['amount'] ?? 0) > 0
                        && ! empty($v['currency'])
                        && ! empty($v['bank_account_id']),
                );

                if (! empty($vueltos)) {
                    foreach ($vueltos as $vuelto) {
                        $this->registerVuelto(
                            (float) $vuelto['amount'],
                            $vuelto['currency'],
                            (int) $vuelto['bank_account_id'],
                            $validated,
                            $lastActivePayment,
                        );
                    }
                } elseif ($excessInDoc > 0) {
                    $payCurrency = $lastActivePayment['payment_currency']
                        ?? $validated['payment_currency'];
                    $vueltoBankId = (int) ($lastActivePayment['bank_account_id']
                        ?? $validated['bank_account_id']);

                    $amount = $lastPayRate
                        ? round(Rate::convert($excessInDoc, $lastDocCurrency, $payCurrency, $lastPayRate), 4)
                        : round($excessInDoc, 4);

                    $this->registerVuelto($amount, $payCurrency, $vueltoBankId, $validated, $lastActivePayment);
                }
            }
        });

        DashboardCacheInvalidator::receivables(substr($validated['payment_date'], 0, 7));
    }

    /**
     * Registers the change given back to the customer as an outflow bank
     * transaction linked to the same receivable.
     *
     * @param  array<string, mixed>  $paymentData  Last active payment (fallback metadata).
     * @param  array<string, mixed>  $validated  Top-level validated payload.
     */
    private function registerVuelto(float $amount, string $currency, int $bankId, array $validated, array $paymentData): void
    {
        $acr = AccountsReceivable::findOrFail($paymentData['acr_id']);
        $payDate = $paymentData['payment_date'] ?? $validated['payment_date'];

        $valorcam = $this->rateService->resolveRateForCoin($payDate, $currency);

        $bankTransaction = BankTransaction::create([
            'btr_bank_account_id' => $bankId,
            'btr_type' => BankTransaction::TYPE_OUTFLOW,
            'btr_amount' => round($amount, 4),
            'btr_reference' => $paymentData['payment_reference'] ?? $validated['payment_reference'],
            'btr_description' => BankTransaction::VUELTO_DESCRIPTION,
            'btr_valorcam' => $valorcam,
            'btr_diferencial' => 0,
            'btr_balance' => 0,
            'btr_method' => $paymentData['payment_method'] ?? $validated['payment_method'],
            'btr_transaction_date' => $payDate,
            'btr_coin' => $currency,
        ]);

        $bankTransaction->btr_transactionable()->associate($acr);
        $bankTransaction->save();
    }

    public function updatePayment(Receipt $payment, array $validated): void
    {
        $acr = $payment->accountsReceivable;

        $oldAmount = $payment->rec_amount;
        $acr->acr_balance += $oldAmount;

        $newAmount = round((float) $validated['amount'], 4);
        if ($newAmount > $acr->acr_balance) {
            throw new \RuntimeException('El monto no puede ser mayor a la deuda pendiente de la factura ($'.number_format($acr->acr_balance, 4).').');
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
