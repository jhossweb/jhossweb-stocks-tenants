<?php

declare(strict_types=1);

namespace App\Reports;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ModelExport implements ExportDefinition
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $with
     * @param  list<string>  $columns
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $filters
     * @param  (callable(Builder, array<string, mixed>): void)|null  $applyFilters
     */
    public function __construct(
        private readonly string $key,
        private readonly string $title,
        private readonly string $filename,
        private readonly string $modelClass,
        private readonly array $with = [],
        private readonly array $columns = [],
        private readonly array $labels = [],
        private readonly array $filters = [],
        private $applyFilters = null,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function headers(): array
    {
        return array_map(
            fn (string $column): string => $this->labels[$column] ?? $column,
            $this->columns(),
        );
    }

    public function rows(): iterable
    {
        $query = $this->modelClass::query()->with($this->with);

        if ($this->applyFilters !== null) {
            ($this->applyFilters)($query, $this->filters);
        }

        $columns = $this->columns();

        foreach ($query->cursor() as $model) {
            $row = [];

            foreach ($columns as $column) {
                $value = $model->getAttribute($column);

                if ($value instanceof BackedEnum) {
                    $value = $value->value;
                } elseif ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                $row[] = $value;
            }

            yield $row;
        }
    }

    /**
     * @return list<string>
     */
    private function columns(): array
    {
        if ($this->columns !== []) {
            return $this->columns;
        }

        $model = new $this->modelClass;

        return Schema::getColumnListing($model->getTable());
    }
}
