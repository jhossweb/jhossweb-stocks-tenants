<?php

namespace App\Actions\Sales\Quote;

use App\Enum\Sales\QuoteStatus;
use App\Enum\Sales\StatusSales;
use App\Exceptions\InsufficientStockException;
use App\Models\MovementsSale;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Services\Finance\RateService;
use RuntimeException;

class ConvertToSaleAction
{
    public function __construct(
        protected RateService $rateService,
        protected ValidateQuoteStockAction $validateStock,
    ) {}

    /**
     * Convierte una cotización en factura de venta (FACV).
     * Dispara stock y CxC a través de los observers.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(SaleOrder $quote, array $data): SaleOrder
    {
        if ($quote->sls_quote_status === QuoteStatus::CONVERTED->value) {
            throw new RuntimeException('La cotización ya fue convertida a factura.');
        }

        $availability = $this->validateStock->handle($quote);

        if (! $availability['ok']) {
            throw new InsufficientStockException(
                'No hay existencias suficientes para convertir la cotización a factura.',
                $availability['missing'],
            );
        }

        $transactionType = TransactionType::where('trans_type_code', 'FACV')->first();

        if (! $transactionType) {
            throw new RuntimeException('Tipo de transacción FACV no encontrado. Ejecute los seeders.');
        }

        $valorcam = $this->rateService->resolveRateForCoin(
            $quote->sls_date?->format('Y-m-d'),
            $quote->sls_coin,
        );

        $sale = new SaleOrder;
        $sale->sls_number = $data['sls_number'];
        $sale->sls_date = $quote->sls_date;
        $sale->sls_trans_type_code = 'FACV';
        $sale->sls_customer_id = $quote->sls_customer_id;
        $sale->sls_valorcam = $valorcam;
        $sale->sls_coin = $quote->sls_coin;
        $sale->sls_subtotal = $quote->sls_subtotal;
        $sale->sls_exento = $quote->sls_exento;
        $sale->sls_iva = $quote->sls_iva;
        $sale->sls_total = $quote->sls_total;
        $sale->sls_payment_condition = 'cash';
        $sale->sls_credit_days = 0;
        $sale->sls_due_date = $quote->sls_date;
        $sale->sls_reference = $quote->sls_number;
        $sale->sls_status = StatusSales::PENDING->value;
        $sale->save();

        foreach ($quote->movements()->get() as $quoteMovement) {
            $movement = new MovementsSale;
            $movement->mvs_number = $sale->sls_number;
            $movement->mvs_trans_type_code = 'FACV';
            $movement->mvs_factor = $transactionType->trans_type_factor;
            $movement->mvs_pro_code = $quoteMovement->mvs_pro_code;
            $movement->mvs_pro_iva = $quoteMovement->mvs_pro_iva;
            $movement->mvs_war_id = $quoteMovement->mvs_war_id;
            $movement->mvs_quantity = $quoteMovement->mvs_quantity;
            $movement->mvs_unit_price = $quoteMovement->mvs_unit_price;
            $movement->mvs_unit_price_ref = $quoteMovement->mvs_unit_price_ref;
            $movement->save();
        }

        $quote->sls_quote_status = QuoteStatus::CONVERTED->value;
        $quote->save();

        return $sale;
    }
}
