<?php

namespace App\Reports\Inventory;

use App\Reports\PdfReport;
use App\Repositories\Inventory\ProductPriceRepository;
use Illuminate\Http\Response;

class PriceListReport extends PdfReport
{
    public function __construct(
        private readonly ProductPriceRepository $productPrices,
    ) {}

    /**
     * PDF de lista de precios para clientes: sin costo ni margen.
     *
     * @param  array{q?: ?string, category_id?: ?int|string}  $filters
     */
    public function download(array $filters = []): Response
    {
        $rows = $this->productPrices->clientList($filters);

        return $this->make('pdf.lista-precios', [
            'rows' => $rows,
            'total' => count($rows),
            'validDate' => now()->format('d/m/Y'),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ], $this->buildFilename('lista_precios', now()->format('Ymd_Hi')));
    }
}
