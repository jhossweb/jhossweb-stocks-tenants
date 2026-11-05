<?php

declare(strict_types=1);

namespace App\Reports\Formats;

use App\Reports\ExportDefinition;
use Symfony\Component\HttpFoundation\Response;

interface ExportFormat
{
    public function key(): string;

    public function extension(): string;

    public function download(ExportDefinition $export): Response;
}
