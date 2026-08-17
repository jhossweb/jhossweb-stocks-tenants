<?php

namespace App\Reports\Sales;

use App\Models\SaleOrder;
use App\Reports\PdfReport;
use Illuminate\Http\Response;

class QuotePdfReport extends PdfReport
{
    public function download(SaleOrder $quote): Response
    {
        $quote->load(['customer', 'movements.product']);

        return $this->make('pdf.documento-venta', [
            'sale' => $quote,
            'customer' => $quote->customer,
            'items' => $quote->movements,
            'documentLabel' => 'Cotización',
            'partyLabel' => 'Cotizar a',
            'documentInfoLabel' => 'Información de la cotización',
            'legend' => 'De aprobar esta cotización, se emitirá documento de venta a la tasa del día.',
        ], $this->buildFilename('cotizacion', $quote->sls_number));
    }
}
