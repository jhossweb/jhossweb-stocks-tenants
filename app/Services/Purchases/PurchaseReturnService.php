<?php

namespace App\Services\Purchases;

use App\DTOs\Purchases\PurchaseReturnDTO;
use App\Enum\Finanzas\TypePayment;
use App\Enum\Purchases\StatusPurchases;
use App\Enum\Tenancy\TenantLimit;
use App\Models\MovementsPurchase;
use App\Models\PurchaseOrder;
use App\Models\TransactionType;
use App\Services\Tenancy\UsageLimiter;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private readonly UsageLimiter $usageLimiter,
    ) {}

    public function store(PurchaseReturnDTO $dto): PurchaseOrder
    {
        $transactionType = TransactionType::where('trans_type_code', 'DEVC')->first();

        if (! $transactionType) {
            throw new \RuntimeException('No existe un tipo de transacción de devolución configurado.');
        }

        $data = $dto->toArray();
        $data['invoice_trans_type_code'] = $transactionType->trans_type_code;
        $data['invoice_status'] = StatusPurchases::PENDING->value;

        $this->usageLimiter->assertCanCreate(TenantLimit::PurchasesReturnsMonthly);

        $purchaseReturn = DB::transaction(function () use ($data, $dto, $transactionType) {
            $return = PurchaseOrder::create($data);

            foreach ($dto->items as $item) {
                $movement = new MovementsPurchase;
                $movement->mov_invoice_number = $return->invoice_number;
                $movement->mov_invoice_trans_type_code = $return->invoice_trans_type_code;
                $movement->mov_factor = $transactionType->trans_type_factor;
                $movement->mov_pro_code = $item['mov_pro_code'];
                $movement->mov_pro_iva = $item['mov_pro_iva'];
                $movement->mov_war_id = $item['mov_war_id'];
                $movement->mov_quantity = $item['mov_quantity'];
                $movement->mov_unit_cost = $item['mov_unit_cost'];
                $movement->mov_unit_cost_ref = $item['mov_unit_cost_ref'];
                $movement->save();
            }

            $this->applyCreditToAccountsPayable($return);

            return $return;
        });

        return $purchaseReturn;
    }

    public function update(PurchaseOrder $purchaseReturn, PurchaseReturnDTO $dto): PurchaseOrder
    {
        $transactionType = TransactionType::where('trans_type_code', 'DEVC')->first();

        if (! $transactionType) {
            throw new \RuntimeException('No existe un tipo de transacción de devolución configurado.');
        }

        $data = $dto->toArray();

        $purchaseReturn = DB::transaction(function () use ($purchaseReturn, $data, $dto, $transactionType) {
            $this->reverseCredit($purchaseReturn);

            $purchaseReturn->update($data);

            $existingIds = $purchaseReturn->movements()->pluck('id')->toArray();
            $newIds = [];

            foreach ($dto->items as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                    $movement = MovementsPurchase::find($item['id']);
                } else {
                    $movement = new MovementsPurchase;
                    $movement->mov_invoice_number = $purchaseReturn->invoice_number;
                    $movement->mov_invoice_trans_type_code = $purchaseReturn->invoice_trans_type_code ?? $transactionType->trans_type_code;
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

            $this->applyCreditToAccountsPayable($purchaseReturn);

            return $purchaseReturn;
        });

        return $purchaseReturn;
    }

    public function destroy(PurchaseOrder $purchaseReturn): void
    {
        DB::transaction(function () use ($purchaseReturn) {
            $this->reverseCredit($purchaseReturn);

            $purchaseReturn->movements()->delete();
            $purchaseReturn->delete();
        });
    }

    private function applyCreditToAccountsPayable(PurchaseOrder $return): void
    {
        if (! $return->invoice_reference) {
            return;
        }

        $originalPurchase = PurchaseOrder::where('invoice_number', $return->invoice_reference)->first();

        if (! $originalPurchase) {
            return;
        }

        $ap = $originalPurchase->accountsPayable;

        if (! $ap) {
            return;
        }

        $amountToApply = (float) $return->invoice_total;

        if ($amountToApply <= 0) {
            return;
        }

        $ap->registerPayment($amountToApply, TypePayment::RETURN_CREDIT->value, null, $return->invoice_number);
    }

    private function reverseCredit(PurchaseOrder $return): void
    {
        if (! $return->invoice_reference) {
            return;
        }

        $originalPurchase = PurchaseOrder::where('invoice_number', $return->invoice_reference)->first();

        if (! $originalPurchase) {
            return;
        }

        $ap = $originalPurchase->accountsPayable;

        if (! $ap) {
            return;
        }

        $payment = $ap->payments()
            ->where('apy_type', TypePayment::RETURN_CREDIT->value)
            ->where('apy_invoice_reference', $return->invoice_number)
            ->first();

        if (! $payment) {
            return;
        }

        $ap->reversePayment($payment);
    }
}
