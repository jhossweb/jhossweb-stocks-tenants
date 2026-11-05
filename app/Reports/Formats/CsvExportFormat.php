<?php

declare(strict_types=1);

namespace App\Reports\Formats;

use App\Reports\ExportDefinition;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportFormat implements ExportFormat
{
    public function key(): string
    {
        return 'csv';
    }

    public function extension(): string
    {
        return 'csv';
    }

    public function download(ExportDefinition $export): Response
    {
        $filename = $export->filename().'.csv';

        return new StreamedResponse(function () use ($export): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $export->headers());

            foreach ($export->rows() as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
