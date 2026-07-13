<?php

namespace App\Services\Purchases;

use App\Enum\Purchases\StatusPurchases;
use App\Models\MovementsPurchase;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\TransactionType;
use App\Services\Finance\RateService;

class PurchaseOrderService
{
    public function __construct(
        protected RateService $rateService,
    ) {}

    public function create(array $validated): PurchaseOrder
    {
        $transactionType = TransactionType::where('trans_type_code', 'FAC')->first();

        $valorcam = $this->rateService->resolveRateForCoin(
            $validated['invoice_date'],
            $validated['invoice_coin'],
        );

        $purchase = new PurchaseOrder;
        $purchase->invoice_number = $validated['invoice_number'];
        $purchase->invoice_date = $validated['invoice_date'];
        $purchase->invoice_trans_type_code = $transactionType->trans_type_code;
        $purchase->invoice_supplier_id = $validated['invoice_supplier_id'];
        $purchase->invoice_valorcam = $valorcam;
        $purchase->invoice_coin = $validated['invoice_coin'];
        $purchase->invoice_subtotal = $validated['invoice_subtotal'];
        $purchase->invoice_exento = $validated['invoice_exento'];
        $purchase->invoice_iva = $validated['invoice_iva'];
        $purchase->invoice_total = $validated['invoice_total'];
        $purchase->invoice_status = StatusPurchases::PENDING->value;
        $purchase->save();

        foreach ($validated['items'] as $item) {
            $product = Product::where('pro_code', $item['mov_pro_code'])->first();

            if ($product && $product->pro_und->value === 'und' && $item['mov_quantity'] != (int) $item['mov_quantity']) {
                throw new \RuntimeException("{$product->pro_name} se compra por unidades enteras.");
            }

            $movement = new MovementsPurchase;
            $movement->mov_invoice_number = $purchase->invoice_number;
            $movement->mov_invoice_trans_type_code = $purchase->invoice_trans_type_code;
            $movement->mov_factor = $transactionType->trans_type_factor;
            $movement->mov_pro_code = $item['mov_pro_code'];
            $movement->mov_pro_iva = $item['mov_pro_iva'];
            $movement->mov_war_id = $item['mov_war_id'];
            $movement->mov_quantity = $item['mov_quantity'];
            $movement->mov_unit_cost = $item['mov_unit_cost'];
            $movement->mov_unit_cost_ref = $item['mov_unit_cost_ref'];
            $movement->save();
        }

        return $purchase;
    }

    public function update(PurchaseOrder $purchase, array $validated): PurchaseOrder
    {
        $transactionType = TransactionType::where('trans_type_code', 'FAC')->first();

        $valorcam = $this->rateService->resolveRateForCoin(
            $validated['invoice_date'],
            $validated['invoice_coin'],
        );

        $purchase->invoice_number = $validated['invoice_number'];
        $purchase->invoice_date = $validated['invoice_date'];
        $purchase->invoice_supplier_id = $validated['invoice_supplier_id'];
        $purchase->invoice_valorcam = $valorcam;
        $purchase->invoice_coin = $validated['invoice_coin'];
        $purchase->invoice_subtotal = $validated['invoice_subtotal'];
        $purchase->invoice_exento = $validated['invoice_exento'];
        $purchase->invoice_iva = $validated['invoice_iva'];
        $purchase->invoice_total = $validated['invoice_total'];
        $purchase->save();

        $existingIds = $purchase->movements()->pluck('id')->toArray();
        $newIds = [];

        foreach ($validated['items'] as $item) {
            $product = Product::where('pro_code', $item['mov_pro_code'])->first();

            if ($product && $product->pro_und->value === 'und' && $item['mov_quantity'] != (int) $item['mov_quantity']) {
                throw new \RuntimeException("{$product->pro_name} se compra por unidades enteras.");
            }

            if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                $movement = MovementsPurchase::find($item['id']);
            } else {
                $movement = new MovementsPurchase;
                $movement->mov_invoice_number = $purchase->invoice_number;
                $movement->mov_invoice_trans_type_code = $purchase->invoice_trans_type_code ?? $transactionType->trans_type_code;
                $movement->mov_factor = $transactionType->trans_type_factor;
            }

            $movement->mov_pro_code = $item['mov_pro_code'];
            $movement->mov_pro_iva = $item['mov_pro_iva'];
            $movement->mov_war_id = $item['mov_war_id'];
            $movement->mov_quantity = $item['mov_quantity'];
            $movement->mov_unit_cost = $item['mov_unit_cost'];
            $movement->mov_unit_cost_ref = $item['mov_unit_cost_ref'];
            $movement->save();

            $newIds[] = $movement->id;
        }

        $itemsToDelete = array_diff($existingIds, $newIds);
        if (! empty($itemsToDelete)) {
            MovementsPurchase::whereIn('id', $itemsToDelete)->delete();
        }

        return $purchase;
    }
}
