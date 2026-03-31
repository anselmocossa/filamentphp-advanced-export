<?php

namespace Filament\AdvancedExport\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * CSV export class for tabular data exports.
 *
 * Uses FromCollection + WithHeadings for clean CSV output
 * with user-selected columns and custom titles.
 */
class CsvExport implements FromCollection, WithHeadings
{
    /**
     * Create a new CSV export instance.
     *
     * @param  Collection  $records  The records to export
     * @param  array<array{field: string, title: string}>  $columnsConfig  Column configuration
     */
    public function __construct(
        protected Collection $records,
        protected array $columnsConfig
    ) {}

    /**
     * Get the headings for the CSV export.
     *
     * @return array<string>
     */
    public function headings(): array
    {
        return array_map(
            fn (array $column) => $column['title'] ?? 'Untitled',
            $this->columnsConfig
        );
    }

    /**
     * Get the collection of rows for the CSV export.
     *
     * Each row contains only the values for the configured columns.
     */
    public function collection(): Collection
    {
        $fields = array_map(
            fn (array $column) => $column['field'] ?? '',
            $this->columnsConfig
        );

        return $this->records->map(function ($record) use ($fields) {
            $row = [];
            foreach ($fields as $field) {
                if (str_contains($field, '.')) {
                    // Handle dot notation for relationships
                    $row[] = data_get($record, $field, '-');
                } else {
                    $row[] = $record->{$field} ?? '-';
                }
            }

            return $row;
        });
    }

    /**
     * Get the columns configuration.
     *
     * @return array<array{field: string, title: string}>
     */
    public function getColumnsConfig(): array
    {
        return $this->columnsConfig;
    }

    /**
     * Get the records being exported.
     */
    public function getRecords(): Collection
    {
        return $this->records;
    }
}
