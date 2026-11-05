<?php

declare(strict_types=1);

namespace App\Reports;

use App\Reports\Formats\ExportFormat;
use Symfony\Component\HttpFoundation\Response;

class ExportManager
{
    /**
     * @param  array<string, ExportFormat>  $formats
     */
    public function __construct(private readonly array $formats) {}

    public function download(ExportDefinition $export, string $format): Response
    {
        $resolved = $this->formats[$format] ?? null;

        if ($resolved === null) {
            abort(422, 'Formato de exportación no válido.');
        }

        return $resolved->download($export);
    }

    /**
     * @return list<string>
     */
    public function availableFormats(): array
    {
        return array_keys($this->formats);
    }
}
