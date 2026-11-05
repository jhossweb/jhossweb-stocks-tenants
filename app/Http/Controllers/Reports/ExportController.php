<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Reports\ExportManager;
use App\Reports\ExportRegistry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $key = (string) $request->route('export');
        $format = (string) $request->query('format', 'csv');

        $definition = ExportRegistry::make($key, $request->query());

        return app(ExportManager::class)->download($definition, $format);
    }
}
