<?php

namespace Filament\AdvancedExport\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

/**
 * Advanced export class for dynamic column exports.
 *
 * Uses a Blade view to generate the Excel content with
 * user-selected columns and custom titles.
 */
class AdvancedExport implements FromView
{
    /**
     * Create a new advanced export instance.
     *
     * @param  array<array{field: string, title: string}>  $columnsConfig
     * @param  array<string, mixed>  $viewData
     */
    public function __construct(
        protected Collection $records,
        protected array $columnsConfig,
        protected string $viewName,
        protected array $viewData
    ) {}

    /**
     * Get the view for the export.
     */
    public function view(): View
    {
        return view($this->viewName, $this->viewData);
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
