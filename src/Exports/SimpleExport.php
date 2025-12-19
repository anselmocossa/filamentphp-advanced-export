<?php

namespace Filament\AdvancedExport\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

/**
 * Simple export class for static column exports.
 *
 * Uses a Blade view to generate the Excel content with
 * all available columns.
 */
class SimpleExport implements FromView
{
    /**
     * Create a new simple export instance.
     *
     * @param  array<string, mixed>  $viewData
     */
    public function __construct(
        protected Collection $records,
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
}
