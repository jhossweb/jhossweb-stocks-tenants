<?php

namespace App\Services\Sales\SaleReturn;

use App\DTOs\Sales\SaleReturn\SaleReturnDTO;
use App\Enum\Finanzas\TypeReceipt;
use App\Enum\Sales\StatusSales;
use App\Enum\Tenancy\TenantLimit;
use App\Models\MovementsSale;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Services\Tenancy\UsageLimiter;

class SaleReturnService
{
    public function __construct(
        private readonly UsageLimiter $usageLimiter,
    ) {}

    public function store(SaleReturnDTO $dto): SaleOrder
    {
        $this->usageLimiter->assertCanCreate(TenantLimit::SalesReturnsMonthly);

        $originalSale = SaleOrder::where('sls_number', $dto->slsReference)
            ->where('sls_trans_type_code', 'FACV')
            ->first();

        if (! $originalSale) {
            throw new \RuntimeException('La factura de venta referenciada no existe o no es válida.');
        }

        if ($originalSale->childrenReturns()->exists()) {
            throw new \RuntimeException('Esta factura ya tiene una devolución registrada.');
        }

        $transactionType = TransactionType::where('trans_type_code', 'DEVV')->first();

        if (! $transactionType) {
            throw new \RuntimeException('Tipo de transacción DEVV no encontrado. Ejecute los seeders.');
        }

        $data = $dto->toArray();
        $data['sls_trans_type_code'] = $transactionType->trans_type_code;
        $data['sls_status'] = StatusSales::PENDING->value;

        $return = SaleOrder::create($data);

        foreach ($dto->items as $item) {
            $this->validateAvailableQty($dto->slsReference, $item);
            $this->createMovement($return, $transactionType, $item);
        }

        $this->applyCreditToAccountsReceivable($originalSale, $return);

        return $return;
    }

    public function update(SaleOrder $return, SaleReturnDTO $dto): SaleOrder
    {
        $transactionType = TransactionType::where('trans_type_code', 'DEVV')->first();

        if (! $transactionType) {
            throw new \RuntimeException('Tipo de transacción DEVV no encontrado. Ejecute los seeders.');
        }

        $data = $dto->toArray();
        unset($data['sls_reference']);

        $return->update($data);

        $existingIds = $return->movements()->pluck('id')->toArray();
        $newIds = [];

        foreach ($dto->items as $item) {
            if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                $movement = MovementsSale::find($item['id']);
            } else {
                $movement = new MovementsSale;
                $movement->mvs_number = $return->sls_number;
                $movement->mvs_trans_type_code = $return->sls_trans_type_code ?? $transactionType->trans_type_code;
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

        $this->reapplyCredit($return);

        return $return;
    }

    public function destroy(SaleOrder $return): void
    {
        $this->reverseCredit($return);

        $return->movements()->delete();
        $return->delete();
    }

    private function applyCreditToAccountsReceivable(SaleOrder $originalSale, SaleOrder $return): void
    {
        $ar = $originalSale->accountsReceivable;

        if (! $ar || ! in_array($ar->acr_status, [StatusSales::PENDING->value, StatusSales::PARTIAL->value])) {
            return;
        }

        $amountToApply = min((float) $return->sls_total, (float) $ar->acr_balance);

        if ($amountToApply <= 0) {
            return;
        }

        $ar->registerPayment($amountToApply, TypeReceipt::RETURN_CREDIT->value);

        $return->sls_status = $ar->acr_status;
        $return->save();
    }

    private function reverseCredit(SaleOrder $return): void
    {
        if (! $return->sls_reference) {
            return;
        }

        $originalSale = SaleOrder::where('sls_number', $return->sls_reference)->first();

        if (! $originalSale) {
            return;
        }

        $ar = $originalSale->accountsReceivable;

        if (! $ar) {
            return;
        }

        $receipt = $ar->payments()
            ->where('rec_type', TypeReceipt::RETURN_CREDIT->value)
            ->first();

        if (! $receipt) {
            return;
        }

        $ar->reversePayment($receipt);
        $receipt->delete();

        $return->sls_status = StatusSales::PENDING->value;
        $return->save();
    }

    private function reapplyCredit(SaleOrder $return): void
    {
        $this->reverseCredit($return);

        if (! $return->sls_reference) {
            return;
        }

        $originalSale = SaleOrder::where('sls_number', $return->sls_reference)->first();

        if (! $originalSale) {
            return;
        }

        $this->applyCreditToAccountsReceivable($originalSale, $return);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function validateAvailableQty(string $slsReference, array $item): void
    {
        $originalQty = MovementsSale::where('mvs_number', $slsReference)
            ->where('mvs_pro_code', $item['mvs_pro_code'])
            ->sum('mvs_quantity');

        $returnedQty = MovementsSale::whereHas('saleOrder', function ($q) use ($slsReference) {
            $q->where('sls_reference', $slsReference);
        })
            ->where('mvs_pro_code', $item['mvs_pro_code'])
            ->sum('mvs_quantity');

        $availableQty = $originalQty - $returnedQty;

        if ($item['mvs_quantity'] > $availableQty) {
            throw new \RuntimeException(
                "La cantidad del producto {$item['mvs_pro_code']} excede la disponible para devolver. Disponible: {$availableQty}"
            );
        }
    }

    private function createMovement(SaleOrder $return, TransactionType $transactionType, array $item): void
    {
        $movement = new MovementsSale;
        $movement->mvs_number = $return->sls_number;
        $movement->mvs_trans_type_code = $return->sls_trans_type_code;
        $movement->mvs_factor = $transactionType->trans_type_factor;
        $movement->mvs_pro_code = $item['mvs_pro_code'];
        $movement->mvs_pro_iva = $item['mvs_pro_iva'];
        $movement->mvs_war_id = $item['mvs_war_id'];
        $movement->mvs_quantity = $item['mvs_quantity'];
        $movement->mvs_unit_price = $item['mvs_unit_price'];
        $movement->mvs_unit_price_ref = $item['mvs_unit_price_ref'];
        $movement->save();
    }
}
