<?php

declare(strict_types=1);

namespace App\Reports\Formats;

use App\Reports\ExportDefinition;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PdfExportFormat implements ExportFormat
{
    public function key(): string
    {
        return 'pdf';
    }

    public function extension(): string
    {
        return 'pdf';
    }

    public function download(ExportDefinition $export): Response
    {
        $maxRows = (int) config('exports.pdf_max_rows', 5000);
        $rows = [];
        $count = 0;

        foreach ($export->rows() as $row) {
            if ($count >= $maxRows) {
                break;
            }

            $rows[] = $row;
            $count++;
        }

        $pdf = Pdf::loadView('exports.table', [
            'title' => $export->title(),
            'headers' => $export->headers(),
            'rows' => $rows,
            'truncated' => $count >= $maxRows,
            'maxRows' => $maxRows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($export->filename().'.pdf');
    }
}
