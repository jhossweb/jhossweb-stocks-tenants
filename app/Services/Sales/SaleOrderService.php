<?php

namespace App\Services\Sales;

use App\Enum\Sales\StatusSales;
use App\Models\MovementsSale;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Services\Finance\RateService;

class SaleOrderService
{
    public function __construct(
        protected RateService $rateService,
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

        return $sale;
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
