<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Ekspor laporan dinamis dari query + daftar kolom.
 *
 * @implements WithMapping<object>
 */
class ReportExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @param  array<int, array{key: string, label: string}>  $columns
     */
    public function __construct(
        private readonly Builder $query,
        private readonly array $columns,
    ) {}

    /** @return \Illuminate\Database\Eloquent\Builder<*> */
    public function query(): Builder
    {
        return $this->query;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return array_column($this->columns, 'label');
    }

    /** @return array<int, mixed> */
    public function map($row): array
    {
        return array_map(
            fn (array $column) => data_get($row, $column['key']) ?? '',
            $this->columns,
        );
    }
}
