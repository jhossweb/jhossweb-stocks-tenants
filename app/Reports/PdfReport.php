<?php

namespace App\Reports;

use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

abstract class PdfReport
{
    protected function make(string $view, array $data, string $filename): Response
    {
        $company = Company::current();
        $companyName = $company?->cmp_name ?? config('app.name');

        $pdf = Pdf::loadView($view, compact('company', 'companyName') + $data);

        return $pdf->download($filename);
    }

    protected function buildFilename(string $dominio, string $detalle, string $extension = 'pdf'): string
    {
        $detalle = preg_replace('/[^\w\-.]+/u', '_', $detalle) ?? $detalle;

        return sprintf('%s_%s.%s', $dominio, $detalle, $extension);
    }
}
