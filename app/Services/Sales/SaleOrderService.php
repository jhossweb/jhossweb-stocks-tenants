<?php

namespace App\Services\Sales;

use App\Enum\Sales\QuoteStatus;
use App\Enum\Sales\StatusSales;
use App\Models\MovementsSale;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Services\Finance\Cobranza\ReceiptService;
use App\Services\Finance\RateService;

class SaleOrderService
{
    public function __construct(
        protected RateService $rateService,
        protected ReceiptService $receiptService,
    ) {}

    public function create(array $validated): SaleOrder
    {
        $transactionType = TransactionType::where('trans_type_code', 'FACV')->first();

        $valorcam = $this->rateService->resolveRateForCoin(
            $validated['sls_date'],
            $validated['sls_coin'],
        );

        $sale = new SaleOrder;
        $sale->sls_number = $validated['sls_number'];
        $sale->sls_date = $validated['sls_date'];
        $sale->sls_trans_type_code = $transactionType->trans_type_code;
        $sale->sls_customer_id = $validated['sls_customer_id'];
        $sale->sls_valorcam = $valorcam;
        $sale->sls_coin = $validated['sls_coin'];
        $sale->sls_subtotal = $validated['sls_subtotal'];
        $sale->sls_exento = $validated['sls_exento'];
        $sale->sls_iva = $validated['sls_iva'];
        $sale->sls_total = $validated['sls_total'];
        $sale->sls_payment_condition = $validated['sls_payment_condition'];
        $sale->sls_credit_days = $validated['sls_credit_days'];
        $sale->sls_due_date = $validated['sls_due_date'];
        $sale->sls_reference = $validated['sls_reference'] ?? null;
        $sale->sls_status = StatusSales::PENDING->value;
        $sale->save();

        foreach ($validated['items'] as $item) {
            $movement = new MovementsSale;
            $movement->mvs_number = $sale->sls_number;
            $movement->mvs_trans_type_code = $sale->sls_trans_type_code;
            $movement->mvs_factor = $transactionType->trans_type_factor;
            $movement->mvs_pro_code = $item['mvs_pro_code'];
            $movement->mvs_pro_iva = $item['mvs_pro_iva'];
            $movement->mvs_war_id = $item['mvs_war_id'];
            $movement->mvs_quantity = $item['mvs_quantity'];
            $movement->mvs_unit_price = $item['mvs_unit_price'];
            $movement->mvs_unit_price_ref = $item['mvs_unit_price_ref'];
            $movement->save();
        }

        $this->markSourceQuoteConverted($sale);

        return $sale;
    }

    /**
     * Marks the originating quote as converted when a sale references it.
     */
    private function markSourceQuoteConverted(SaleOrder $sale): void
    {
        if (empty($sale->sls_reference)) {
            return;
        }

        $quote = SaleOrder::where('sls_number', $sale->sls_reference)
            ->where('sls_trans_type_code', 'COT')
            ->first();

        if ($quote && $quote->sls_quote_status !== QuoteStatus::CONVERTED->value) {
            $quote->sls_quote_status = QuoteStatus::CONVERTED->value;
            $quote->save();
        }
    }

    /**
     * Create a sale and, if its payment condition is "cash", register the
     * collection in the same atomic transaction.
     */
    public function createWithPayment(array $validated): SaleOrder
    {
        $sale = $this->create($validated);

        if ($validated['sls_payment_condition'] === 'cash') {
            $receivable = $sale->accountsReceivable;

            if (! $receivable) {
                throw new \RuntimeException('No se pudo generar la cuenta por cobrar de la venta.');
            }

            $this->receiptService->processPayments($this->buildPaymentPayload($validated, $receivable->id));
        }

        return $sale;
    }

    /**
     * Update a sale and, when its payment condition is "cash" and there is an
     * outstanding balance, register the collection in the same transaction.
     */
    public function updateWithPayment(SaleOrder $sale, array $validated): SaleOrder
    {
        $wasPendingCash = $sale->sls_payment_condition === 'cash'
            && $sale->accountsReceivable
            && (float) $sale->accountsReceivable->acr_balance > 0;

        $this->update($sale, $validated);

        if ($wasPendingCash && $validated['sls_payment_condition'] === 'cash' && isset($validated['payment'])) {
            $receivable = $sale->accountsReceivable;

            if (! $receivable) {
                throw new \RuntimeException('No se pudo generar la cuenta por cobrar de la venta.');
            }

            $this->receiptService->processPayments($this->buildPaymentPayload($validated, $receivable->id));
        }

        return $sale;
    }

    /**
     * Builds the payment payload for ReceiptService from the validated request.
     */
    private function buildPaymentPayload(array $validated, int $receivableId): array
    {
        return [
            'payment_date' => $validated['payment']['payment_date'] ?? now()->toDateString(),
            'customer_id' => $validated['sls_customer_id'],
            'apply_vuelto' => true,
            'vueltos' => array_map(fn (array $v): array => [
                'amount' => $v['vuelto_amount'],
                'currency' => $v['vuelto_currency'],
                'bank_account_id' => $v['vuelto_bank_account_id'],
            ], $validated['payment']['vueltos'] ?? []),
            'payments' => array_map(fn (array $p): array => [
                'acr_id' => $receivableId,
                'amount' => $p['payment_amount'],
                'payment_currency' => $p['payment_currency'],
                'payment_rate' => $p['payment_rate'],
                'payment_method' => $p['payment_method'],
                'payment_reference' => $p['payment_reference'],
                'payment_description' => $p['payment_description'] ?? null,
                'bank_account_id' => $p['bank_account_id'],
            ], $validated['payment']['payments']),
        ];
    }

    public function update(SaleOrder $sale, array $validated): SaleOrder
    {
        $transactionType = TransactionType::where('trans_type_code', 'FACV')->first();

        $valorcam = $this->rateService->resolveRateForCoin(
            $validated['sls_date'],
            $validated['sls_coin'],
        );

        $sale->sls_number = $validated['sls_number'];
        $sale->sls_date = $validated['sls_date'];
        $sale->sls_customer_id = $validated['sls_customer_id'];
        $sale->sls_valorcam = $valorcam;
        $sale->sls_coin = $validated['sls_coin'];
        $sale->sls_subtotal = $validated['sls_subtotal'];
        $sale->sls_exento = $validated['sls_exento'];
        $sale->sls_iva = $validated['sls_iva'];
        $sale->sls_total = $validated['sls_total'];
        $sale->sls_payment_condition = $validated['sls_payment_condition'];
        $sale->sls_credit_days = $validated['sls_credit_days'];
        $sale->sls_due_date = $validated['sls_due_date'];
        $sale->save();

        $existingIds = $sale->movements()->pluck('id')->toArray();
        $newIds = [];

        foreach ($validated['items'] as $item) {
            if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                $movement = MovementsSale::find($item['id']);
            } else {
                $movement = new MovementsSale;
                $movement->mvs_number = $sale->sls_number;
                $movement->mvs_trans_type_code = $sale->sls_trans_type_code ?? $transactionType->trans_type_code;
                $movement->mvs_factor = $transactionType->trans_type_factor;
            }

            $movement->mvs_pro_code = $item['mvs_pro_code'];
            $movement->mvs_pro_iva = $item['mvs_pro_iva'];
            $movement->mvs_war_id = $item['mvs_war_id'];
            $movement->mvs_quantity = $item['mvs_quantity'];
            $movement->mvs_unit_price = $item['mvs_unit_price'];
            $movement->mvs_unit_price_ref = $item['mvs_unit_price_ref'];
            $movement->save();

            $newIds[] = $movement->id;
        }

        $itemsToDelete = array_diff($existingIds, $newIds);
        if (! empty($itemsToDelete)) {
            MovementsSale::whereIn('id', $itemsToDelete)->delete();
        }

        return $sale;
    }
}
