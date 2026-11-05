<?php

declare(strict_types=1);

use App\Reports\Formats\CsvExportFormat;
use App\Reports\Formats\PdfExportFormat;

return [
    'pdf_max_rows' => (int) env('EXPORT_PDF_MAX_ROWS', 5000),

    // Para agregar Excel en el futuro: crear XlsxExportFormat y registrarlo aquí.
    // No hay que tocar controladores ni definiciones de exportación.
    'formats' => [
        'csv' => CsvExportFormat::class,
        'pdf' => PdfExportFormat::class,
    ],
];
