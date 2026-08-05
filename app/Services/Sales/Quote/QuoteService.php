<?php

namespace App\Services\Sales\Quote;

use App\DTOs\Sales\Quote\QuoteDTO;
use App\Enum\Sales\QuoteStatus;
use App\Models\MovementsSale;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Services\DocumentNumberingService;
use App\Services\Finance\RateService;

class QuoteService
{
    public function __construct(
        protected DocumentNumberingService $documentNumbering,
        protected RateService $rateService,
    ) {}

    public function create(QuoteDTO $dto): SaleOrder
    {
        $transactionType = $this->getTransactionType();
        $slsNumber = $this->documentNumbering->nextNumber('COT');

        $quote = new SaleOrder;
        $quote->sls_number = $slsNumber;
        $quote->sls_date = $dto->slsDate;
        $quote->sls_trans_type_code = $transactionType->trans_type_code;
        $quote->sls_customer_id = $dto->slsCustomerId;
        $quote->sls_valorcam = $dto->slsValorcam;
        $quote->sls_coin = $dto->slsCoin;
        $quote->sls_subtotal = $dto->slsSubtotal;
        $quote->sls_exento = $dto->slsExento;
        $quote->sls_iva = $dto->slsIva;
        $quote->sls_total = $dto->slsTotal;
        $quote->sls_payment_condition = 'cash';
        $quote->sls_credit_days = 0;
        $quote->sls_due_date = $dto->slsDate;
        $quote->sls_quote_status = QuoteStatus::PENDING->value;
        $quote->save();

        $this->createMovements($quote, $transactionType, $dto->items);

        return $quote;
    }

    public function update(SaleOrder $quote, QuoteDTO $dto): SaleOrder
    {
        $transactionType = $this->getTransactionType();

        $quote->update($dto->toArray());

        $existingIds = $quote->movements()->pluck('id')->toArray();
        $newIds = [];

        foreach ($dto->items as $item) {
            if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                $movement = MovementsSale::find($item['id']);
            } else {
                $movement = new MovementsSale;
                $movement->mvs_number = $quote->sls_number;
                $movement->mvs_trans_type_code = $quote->sls_trans_type_code;
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

        return $quote;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function createMovements(SaleOrder $quote, TransactionType $transactionType, array $items): void
    {
        foreach ($items as $item) {
            $movement = new MovementsSale;
            $movement->mvs_number = $quote->sls_number;
            $movement->mvs_trans_type_code = $quote->sls_trans_type_code;
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

    private function getTransactionType(): TransactionType
    {
        $transactionType = TransactionType::where('trans_type_code', 'COT')->first();

        if (! $transactionType) {
            throw new \RuntimeException('Tipo de transacción COT no encontrado. Ejecute los seeders.');
        }

        return $transactionType;
    }
}
