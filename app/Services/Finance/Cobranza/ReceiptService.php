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

        $bankMovements = $validated['bank_movements'] ?? [];

        if (empty($activePayments)) {
            throw new \RuntimeException('Debe ingresar al menos un monto a cobrar.');
        }

        if (empty($bankMovements)) {
            throw new \RuntimeException('Debe registrar al menos un movimiento bancario.');
        }

        DB::transaction(function () use ($activePayments, $bankMovements, $validated) {
            $payDate = $validated['payment_date'];
            $payRate = $this->rateService->getBestRateForDate($payDate);

            if (! $payRate) {
                throw new \RuntimeException('No existe tasa de cambio para la fecha seleccionada.');
            }

            $docCurrency = $this->resolveDocCurrency($activePayments);

            $convertedTotal = 0.0;
            $resolvedMovements = [];

            foreach ($bankMovements as $movement) {
                $payCurrency = strtoupper((string) $movement['currency']);
                $movementAmount = (float) $movement['amount'];

                if ($movementAmount <= 0) {
                    continue;
                }

                $movementInDoc = Rate::convert($movementAmount, $payCurrency, $docCurrency, $payRate);
                $convertedTotal += $movementInDoc;
                $resolvedMovements[] = [
                    'movement' => $movement,
                    'payCurrency' => $payCurrency,
                    'amount' => $movementAmount,
                    'amountInDoc' => $movementInDoc,
                ];
            }

            if (empty($resolvedMovements) || round($convertedTotal, 4) <= 0) {
                throw new \RuntimeException('Debe registrar al menos un movimiento bancario con monto válido.');
            }

            $applyVuelto = (bool) ($validated['apply_vuelto'] ?? false);
            $excessInDoc = 0.0;
            $lastAppliedDoc = null;
            $lastDocCurrency = $docCurrency;
            $lastPayRate = $payRate;

            // FIFO: each movement pays the receivables in order, converting to doc currency.
            $pendingApplicable = $activePayments;
            $lastReceipt = null;

            foreach ($resolvedMovements as $index => $resolved) {
                $movement = $resolved['movement'];
                $payCurrency = $resolved['payCurrency'];
                $movementAmount = $resolved['amount'];
                $amountInDoc = $resolved['amountInDoc'];
                $bankId = (int) $movement['bank_account_id'];
                $payMethod = $movement['method'];
                $payReference = $movement['reference'] ?? '';
                $payDescription = $movement['description'] ?? null;

                // Consume the converted movement value across the receivables being collected.
                $movementDocRemaining = $amountInDoc;
                $appliedHere = 0.0;
                $appliedAcrs = [];

                while ($movementDocRemaining > 0 && ! empty($pendingApplicable)) {
                    $paymentData = $pendingApplicable[0];
                    $acr = AccountsReceivable::with('saleOrder')->findOrFail($paymentData['acr_id']);
                    $docAmount = (float) $paymentData['amount'];

                    $apply = min($movementDocRemaining, $docAmount);
                    if ($apply < 0.0001) {
                        break;
                    }

                    $appliedAcrs[] = $paymentData;
                    $appliedHere += $apply;
                    $movementDocRemaining -= $apply;

                    if ($apply >= $docAmount - 0.0001) {
                        array_shift($pendingApplicable);
                    } else {
                        $paymentData['amount'] = round($docAmount - $apply, 4);
                        $pendingApplicable[0] = $paymentData;
                    }
                }

                $appliedInPayCurrency = Rate::convert($appliedHere, $docCurrency, $payCurrency, $payRate);
                $valorcam = $this->rateService->resolveRateForCoin($payDate, $payCurrency);
                $diferencial = $this->rateService->calculateDiferencial(
                    $appliedHere,
                    $payCurrency,
                    $payRate,
                    $payRate,
                );

                $bankTransaction = BankTransaction::create([
                    'btr_bank_account_id' => $bankId,
                    'btr_type' => 'inflow',
                    'btr_amount' => round($appliedInPayCurrency, 4),
                    'btr_reference' => $payReference,
                    'btr_description' => $payDescription ?: 'Cobro a cliente',
                    'btr_valorcam' => $valorcam,
                    'btr_diferencial' => $diferencial,
                    'btr_balance' => 0,
                    'btr_method' => $payMethod,
                    'btr_transaction_date' => $payDate,
                    'btr_coin' => $payCurrency,
                ]);

                if (! empty($appliedAcrs)) {
                    $firstAcr = $appliedAcrs[0];
                    $bankTransaction->btr_transactionable()->associate(
                        AccountsReceivable::with('saleOrder')->findOrFail($firstAcr['acr_id'])
                    );
                    $bankTransaction->save();

                    // Register the receipts against each receivable covered by the movement.
                    $movementDocRemaining = $amountInDoc;

                    foreach ($appliedAcrs as $appliedAcr) {
                        $acr = AccountsReceivable::with('saleOrder')->findOrFail($appliedAcr['acr_id']);
                        $apply = min($movementDocRemaining, (float) $appliedAcr['amount']);

                        if ($apply < 0.0001) {
                            continue;
                        }

                        $receipt = $acr->registerPayment(
                            $apply,
                            TypeReceipt::RECEIPT->value,
                            $bankTransaction->id,
                            round(Rate::convert($apply, $docCurrency, 'USD', $payRate), 4),
                        );
                        $receipt->rec_diferencial = $diferencial;
                        $receipt->save();
                        $lastReceipt = $receipt;
                        $lastAppliedDoc = $appliedAcr;
                        $movementDocRemaining -= $apply;
                    }
                } else {
                    $bankTransaction->delete();
                }

                $excessInDoc += $amountInDoc - $appliedHere;
            }

            if ($applyVuelto && $lastAppliedDoc && $excessInDoc > 0.0001) {
                $vueltos = array_values(array_filter(
                    $validated['vueltos'] ?? [],
                    fn (array $v): bool => (float) ($v['amount'] ?? 0) > 0
                        && ! empty($v['currency'])
                        && ! empty($v['bank_account_id']),
                ));

                if (! empty($vueltos)) {
                    foreach ($vueltos as $vuelto) {
                        $this->registerVuelto(
                            (float) $vuelto['amount'],
                            $vuelto['currency'],
                            (int) $vuelto['bank_account_id'],
                            $validated,
                            $lastAppliedDoc,
                        );
                    }
                } else {
                    $lastMovement = end($bankMovements);
                    $payCurrency = strtoupper((string) ($lastMovement['currency'] ?? 'USD'));
                    $vueltoBankId = (int) ($lastMovement['bank_account_id'] ?? 0);

                    $amount = $lastPayRate
                        ? round(Rate::convert($excessInDoc, $lastDocCurrency, $payCurrency, $lastPayRate), 4)
                        : round($excessInDoc, 4);

                    $this->registerVuelto($amount, $payCurrency, $vueltoBankId, $validated, $lastAppliedDoc);
                }
            }
        });

        DashboardCacheInvalidator::receivables(substr($validated['payment_date'], 0, 7));
    }

    /**
     * Resolves the single document currency shared by the receivables being
     * collected. All selected invoices must share the same currency.
     *
     * @param  array<int, array<string, mixed>>  $activePayments
     */
    private function resolveDocCurrency(array $activePayments): string
    {
        $currencies = [];

        foreach ($activePayments as $paymentData) {
            $acr = AccountsReceivable::with('saleOrder')->findOrFail($paymentData['acr_id']);
            $currency = strtoupper((string) ($acr->saleOrder->sls_coin ?? 'USD'));
            $currencies[$currency] = true;
        }

        if (count($currencies) > 1) {
            throw new \RuntimeException('No puede cobrar facturas de monedas distintas en una misma operación.');
        }

        return array_key_first($currencies) ?: 'USD';
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
        $payDate = $validated['payment_date'];
        $bankMovements = $validated['bank_movements'] ?? [];
        $lastMovement = end($bankMovements) ?: [];

        $valorcam = $this->rateService->resolveRateForCoin($payDate, $currency);

        $bankTransaction = BankTransaction::create([
            'btr_bank_account_id' => $bankId,
            'btr_type' => BankTransaction::TYPE_OUTFLOW,
            'btr_amount' => round($amount, 4),
            'btr_reference' => $lastMovement['reference'] ?? 'Vuelto',
            'btr_description' => BankTransaction::VUELTO_DESCRIPTION,
            'btr_valorcam' => $valorcam,
            'btr_diferencial' => 0,
            'btr_balance' => 0,
            'btr_method' => $lastMovement['method'] ?? 'EFECTIVO',
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

        DB::transaction(function () use ($payment, $acr, $newAmount, $validated) {
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
                $newPayCoin = strtoupper((string) $validated['payment_currency']);
                $newPayRate = $this->rateService->getBestRateForDate($validated['payment_date']);
                $docCurrency = $acr->saleOrder->sls_coin ?? 'USD';

                $bankTx->btr_bank_account_id = (int) $validated['bank_account_id'];
                $bankTx->btr_transaction_date = $validated['payment_date'];
                $bankTx->btr_reference = $validated['payment_reference'];
                $bankTx->btr_description = $validated['payment_description'];
                $bankTx->btr_amount = $newPayRate
                    ? round(Rate::convert($newAmount, $docCurrency, $newPayCoin, $newPayRate), 4)
                    : round($newAmount, 4);
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
                $payCoin = $bankTx->btr_coin?->value ?? $bankTx->btr_coin;
                $amountInPay = is_string($payCoin) && strtoupper($payCoin) !== 'USD' && $bankTx->btr_valorcam > 0
                    ? round($amount * (float) $bankTx->btr_valorcam, 4)
                    : $amount;

                $bankTx->btr_amount -= $amountInPay;
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
