<?php

namespace App\Reports\Sales;

use App\Models\SaleOrder;
use App\Reports\PdfReport;
use Illuminate\Http\Response;

class SaleInvoiceReport extends PdfReport
{
    public function download(SaleOrder $sale): Response
    {
        $sale->load(['customer', 'movements.product']);

        return $this->make('pdf.documento-venta', [
            'sale' => $sale,
            'customer' => $sale->customer,
            'items' => $sale->movements,
            'documentLabel' => 'Factura',
            'partyLabel' => 'Facturar a',
            'documentInfoLabel' => 'Información de la factura',
            'legend' => null,
        ], $this->buildFilename('venta', $sale->sls_number));
    }
}
