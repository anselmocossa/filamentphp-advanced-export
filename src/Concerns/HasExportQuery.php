<?php

namespace Filament\AdvancedExport\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides query building methods for export functionality.
 */
trait HasExportQuery
{
    /**
     * Get the relationships to eager load for export.
     *
     * @return array<string>
     */
    protected function getExportRelationships(): array
    {
        return $this->getExportRelationshipsForModel();
    }

    /**
     * Define the relationships specific to the model for export.
     *
     * Override this method in your ListRecords class to specify
     * which relationships should be eager loaded.
     *
     * @return array<string>
     */
    protected function getExportRelationshipsForModel(): array
    {
        return [];
    }

    /**
     * Build the base query for export.
     */
    protected function buildExportQuery(array $activeFilters): Builder
    {
        $modelClass = $this->getExportModel();
        $relationships = $this->getExportRelationships();

        $query = $modelClass::query()->with($relationships);
        $this->applyFiltersToQuery($query, $activeFilters);

        return $query;
    }

    /**
     * Apply custom ordering to the query.
     *
     * Override this method to handle special ordering cases,
     * such as ordering by relationship columns.
     */
    protected function applyCustomOrdering(Builder $query, string $orderColumn, string $orderDirection): void
    {
        $query->orderBy($orderColumn, $orderDirection);
    }

    /**
     * Get the view data for export.
     *
     * @return array<string, mixed>
     */
    protected function getExportViewData(\Illuminate\Database\Eloquent\Collection $records, ?array $columnsConfig = null): array
    {
        $modelClass = $this->getExportModel();
        $tableName = (new $modelClass)->getTable();

        $data = [
            $tableName => $records,
        ];

        if ($columnsConfig !== null) {
            $data['columnsConfig'] = $columnsConfig;
        }

        return $data;
    }

    /**
     * Generate the filename for the export.
     */
    protected function generateFileName(string $type = 'export'): string
    {
        $config = $this->getExportConfig();
        $resourceName = strtolower(class_basename(static::$resource));
        $extension = $config->getFileExtension();
        $datetimeFormat = config('advanced-export.file.datetime_format', 'Y-m-d_H-i-s');

        return "{$resourceName}_{$type}_".date($datetimeFormat).".{$extension}";
    }
}
