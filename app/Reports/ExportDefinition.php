<?php

declare(strict_types=1);

namespace App\Reports;

interface ExportDefinition
{
    public function key(): string;

    public function title(): string;

    public function filename(): string;

    /**
     * @return list<string>
     */
    public function headers(): array;

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function rows(): iterable;
}
