<?php

namespace Filament\AdvancedExport\Traits;

use Exception;
use Filament\Actions\Action;
use Filament\AdvancedExport\Concerns\HasExportConfiguration;
use Filament\AdvancedExport\Concerns\HasExportFilters;
use Filament\AdvancedExport\Concerns\HasExportNotifications;
use Filament\AdvancedExport\Concerns\HasExportQuery;
use Filament\AdvancedExport\Exports\AdvancedExport;
use Filament\AdvancedExport\Exports\SimpleExport;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Trait for advanced export functionality in Filament resources.
 *
 * This trait provides customizable export functionality including:
 * - Dynamic column selection
 * - Custom column titles
 * - Configurable ordering
 * - Filter support
 * - View-based export templates
 *
 * @example
 * class ListClientes extends ListRecords
 * {
 *     use HasAdvancedExport;
 *
 *     protected function getHeaderActions(): array
 *     {
 *         return [
 *             $this->getAdvancedExportHeaderAction(),
 *         ];
 *     }
 * }
 */
trait HasAdvancedExport
{
    use HasExportConfiguration;
    use HasExportFilters;
    use HasExportNotifications;
    use HasExportQuery;

    /**
     * Create the advanced export header action.
     */
    protected function getAdvancedExportHeaderAction(): Action
    {
        return Action::make($this->getExportActionName())
            ->label($this->getExportActionLabel())
            ->color($this->getExportActionColor())
            ->icon($this->getExportActionIcon())
            ->form($this->getExportForm())
            ->modalHeading($this->getExportModalHeading())
            ->modalDescription($this->getExportModalDescription())
            ->modalSubmitActionLabel($this->getExportModalSubmitLabel())
            ->action(function (array $data): ?BinaryFileResponse {
                return $this->exportWithCustomColumns(
                    $data['columns'] ?? [],
                    $data['order_column'] ?? 'created_at',
                    $data['order_direction'] ?? 'desc'
                );
            });
    }

    /**
     * Create the export configuration form.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    protected function getExportForm(): array
    {
        $columns = $this->getExportColumns();
        $config = $this->getExportConfig();

        return [
            Select::make('order_column')
                ->label(__('advanced-export::messages.form.order_column.label'))
                ->placeholder(__('advanced-export::messages.form.order_column.placeholder'))
                ->options($columns)
                ->searchable()
                ->default('created_at')
                ->helperText(__('advanced-export::messages.form.order_column.helper')),

            Select::make('order_direction')
                ->label(__('advanced-export::messages.form.order_direction.label'))
                ->options([
                    'asc' => __('advanced-export::messages.form.order_direction.options.asc'),
                    'desc' => __('advanced-export::messages.form.order_direction.options.desc'),
                ])
                ->default('desc')
                ->required()
                ->helperText(__('advanced-export::messages.form.order_direction.helper')),

            Repeater::make('columns')
                ->label(__('advanced-export::messages.form.columns.label'))
                ->schema([
                    Select::make('field')
                        ->label(__('advanced-export::messages.form.columns.field.label'))
                        ->options($columns)
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (mixed $state, callable $set) use ($columns): void {
                            $set('title', $columns[$state] ?? ucfirst(str_replace('_', ' ', $state)));
                        }),
                    TextInput::make('title')
                        ->label(__('advanced-export::messages.form.columns.title.label'))
                        ->placeholder(__('advanced-export::messages.form.columns.title.placeholder'))
                        ->required(),
                ])
                ->default($this->getDefaultExportColumns())
                ->addActionLabel(__('advanced-export::messages.form.columns.add'))
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? __('advanced-export::messages.form.columns.new'))
                ->minItems($config->getMinRequiredColumns())
                ->maxItems($config->getMaxSelectableColumns()),
        ];
    }

    /**
     * Export data with simple configuration.
     */
    protected function exportSimple(): ?BinaryFileResponse
    {
        try {
            $activeFilters = $this->extractActiveFilters();
            $query = $this->buildExportQuery($activeFilters);
            $records = $query->limit($this->getExportLimit())->get();

            if ($records->isEmpty()) {
                $this->showNoDataNotification();

                return null;
            }

            $export = new SimpleExport(
                $records,
                $this->getExportViewName(),
                $this->getExportViewData($records)
            );

            $fileName = $this->generateFileName('simple');

            return Excel::download($export, $fileName);
        } catch (Exception $e) {
            $this->handleExportError($e, 'simple export');

            return null;
        }
    }

    /**
     * Export data with custom column configuration.
     *
     * @param  array<array{field: string, title: string}>  $columnsConfig
     */
    protected function exportWithCustomColumns(
        array $columnsConfig = [],
        string $orderColumn = 'created_at',
        string $orderDirection = 'desc'
    ): ?BinaryFileResponse {
        try {
            if (empty($columnsConfig)) {
                return $this->exportSimple();
            }

            $activeFilters = $this->extractActiveFilters();
            $query = $this->buildExportQuery($activeFilters);
            $this->applyCustomOrdering($query, $orderColumn, $orderDirection);
            $records = $query->limit($this->getExportLimit())->get();

            if ($records->isEmpty()) {
                $this->showNoDataNotification();

                return null;
            }

            $export = new AdvancedExport(
                $records,
                $columnsConfig,
                $this->getAdvancedExportViewName(),
                $this->getExportViewData($records, $columnsConfig)
            );

            $fileName = $this->generateFileName('advanced');

            return Excel::download($export, $fileName);
        } catch (Exception $e) {
            $this->handleExportError($e, 'advanced export');

            return null;
        }
    }
}
