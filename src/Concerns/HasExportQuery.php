<?php

namespace Filament\AdvancedExport\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
     *
     * Uses the resource's getEloquentQuery() to respect any scopes
     * defined on the resource (e.g. filtering by role, tenant, etc.).
     */
    protected function buildExportQuery(array $activeFilters): Builder
    {
        $relationships = $this->getExportRelationships();

        $query = static::$resource::getEloquentQuery()->with($relationships);
        $this->applyFiltersToQuery($query, $activeFilters);

        return $query;
    }

    /**
     * Apply custom ordering to the query.
     *
     * Validates the column name and direction before applying.
     * Dot notation columns (relationships) are skipped gracefully.
     *
     * Override this method to handle special ordering cases,
     * such as ordering by relationship columns with joins.
     */
    protected function applyCustomOrdering(Builder $query, string $orderColumn, string $orderDirection): void
    {
        // Validate direction — only 'asc' or 'desc' allowed
        $orderDirection = strtolower($orderDirection);
        if (! in_array($orderDirection, ['asc', 'desc'], true)) {
            Log::warning("Invalid order direction '{$orderDirection}', falling back to 'desc'");
            $orderDirection = 'desc';
        }

        // Skip dot notation columns (relationship paths) — these need
        // custom join logic that should be implemented in the override
        if (str_contains($orderColumn, '.')) {
            Log::info("Skipping dot notation order column '{$orderColumn}' — override applyCustomOrdering() to handle relationship ordering");

            return;
        }

        // Validate column exists on the model's table or in export columns
        if (! $this->isValidOrderColumn($query, $orderColumn)) {
            Log::warning("Invalid order column '{$orderColumn}', falling back to 'created_at'");
            $orderColumn = 'created_at';
        }

        $query->orderBy($orderColumn, $orderDirection);
    }

    /**
     * Check if a column is valid for ordering.
     *
     * A column is valid if it exists on the database table or
     * is listed in the export columns (without dot notation).
     */
    protected function isValidOrderColumn(Builder $query, string $orderColumn): bool
    {
        // Check if column exists on the database table
        $table = $query->getModel()->getTable();
        if (Schema::hasColumn($table, $orderColumn)) {
            return true;
        }

        // Check if column is in the export columns list (non-dot notation only)
        if (method_exists($this, 'getExportColumns')) {
            $exportColumns = $this->getExportColumns();
            if (array_key_exists($orderColumn, $exportColumns)) {
                return true;
            }
        }

        return false;
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
    protected function generateFileName(string $type = 'export', ?string $extensionOverride = null): string
    {
        $config = $this->getExportConfig();
        $resourceName = strtolower(class_basename(static::$resource));
        $extension = $extensionOverride ?? $config->getFileExtension();
        $datetimeFormat = config('advanced-export.file.datetime_format', 'Y-m-d_H-i-s');

        return "{$resourceName}_{$type}_".date($datetimeFormat).".{$extension}";
    }
}
