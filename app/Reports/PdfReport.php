<?php

namespace App\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

abstract class PdfReport
{
    protected const COMPANY = 'Vidrialum';

    protected function make(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, ['company' => self::COMPANY] + $data);

        return $pdf->download($filename);
    }

    protected function buildFilename(string $dominio, string $detalle, string $extension = 'pdf'): string
    {
        $detalle = preg_replace('/[^\w\-.]+/u', '_', $detalle) ?? $detalle;

        return sprintf('%s_%s.%s', $dominio, $detalle, $extension);
    }
}
