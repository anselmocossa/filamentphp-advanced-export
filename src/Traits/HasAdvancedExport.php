<?php

namespace Filament\AdvancedExport\Traits;

use Exception;
use Filament\Actions\Action;
use Filament\AdvancedExport\Concerns\HasExportConfiguration;
use Filament\AdvancedExport\Concerns\HasExportFilters;
use Filament\AdvancedExport\Concerns\HasExportNotifications;
use Filament\AdvancedExport\Concerns\HasExportQuery;
use Filament\AdvancedExport\Exports\AdvancedExport;
use Filament\AdvancedExport\Exports\CsvExport;
use Filament\AdvancedExport\Exports\SimpleExport;
use Filament\Forms\Components\Placeholder;
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
 * - Shield permission integration (Export:{Resource})
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
     * Check if the current user can export from this resource.
     *
     * If FilamentShield is installed and the Resource uses HasExportPermission,
     * checks for the 'export' permission (e.g., Export:Titular).
     * Without Shield, export is always allowed.
     */
    protected function canExport(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        try {
            $model = static::$resource::getModel();
            $modelInstance = new $model;

            $policy = policy($modelInstance);
            if (method_exists($policy, 'export')) {
                return $user->can('export', $modelInstance);
            }
        } catch (\Throwable) {
            // No policy registered — allow export
        }

        return true;
    }

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
            ->authorize(fn (): bool => $this->canExport())
            ->action(function (array $data): ?BinaryFileResponse {
                return $this->exportWithCustomColumns(
                    $data['columns'] ?? [],
                    $data['order_column'] ?? 'created_at',
                    $data['order_direction'] ?? 'desc',
                    $data['export_format'] ?? 'xlsx'
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
        $recordCount = $this->getExportRecordCount();

        return [
            Placeholder::make('record_count')
                ->label(__('advanced-export::messages.form.record_count.label'))
                ->content(__('advanced-export::messages.form.record_count.content', [
                    'count' => number_format($recordCount),
                    'limit' => number_format($this->getExportLimit()),
                ])),

            Select::make('export_format')
                ->label(__('advanced-export::messages.form.export_format.label'))
                ->options($this->getExportFormatOptions())
                ->default($config->getFileExtension())
                ->required()
                ->helperText(__('advanced-export::messages.form.export_format.helper')),

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
                ->collapsed()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? __('advanced-export::messages.form.columns.new'))
                ->minItems($config->getMinRequiredColumns())
                ->maxItems($config->getMaxSelectableColumns()),
        ];
    }

    /**
     * Get the number of records that will be exported with current filters.
     */
    protected function getExportRecordCount(): int
    {
        try {
            $activeFilters = $this->extractActiveFilters();
            $query = $this->buildExportQuery($activeFilters);

            return min($query->count(), $this->getExportLimit());
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get the available export format options.
     *
     * @return array<string, string>
     */
    protected function getExportFormatOptions(): array
    {
        $formats = $this->getExportConfig()->getSupportedFormats();
        $options = [];

        foreach ($formats as $format) {
            $options[$format] = strtoupper($format);
        }

        return $options;
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
        string $orderDirection = 'desc',
        string $format = 'xlsx'
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

            // Use CSV export class for CSV format
            if ($format === 'csv') {
                $export = new CsvExport($records, $columnsConfig);
                $fileName = $this->generateFileName('advanced', 'csv');

                return Excel::download($export, $fileName, \Maatwebsite\Excel\Excel::CSV);
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
