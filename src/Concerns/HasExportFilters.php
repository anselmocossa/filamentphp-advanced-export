<?php

namespace Filament\AdvancedExport\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Provides filter extraction and application methods for export functionality.
 */
trait HasExportFilters
{
    /**
     * Extract active filters from the Filament table.
     *
     * @return array<string, mixed>
     */
    protected function extractActiveFilters(): array
    {
        $activeFilters = [];

        try {
            $table = $this->getTable();
            $filterNames = array_keys($table->getFilters());

            foreach ($filterNames as $filterName) {
                try {
                    $filterState = $this->getTableFilterState($filterName);

                    if (! empty($filterState)) {
                        $processedFilter = $this->processFilterState($filterName, $filterState);
                        if ($processedFilter !== null) {
                            $activeFilters[$filterName] = $processedFilter;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Error accessing filter state for '{$filterName}': ".$e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Error extracting active filters: '.$e->getMessage());
            $activeFilters = $this->extractFallbackFilters();
        }

        return $activeFilters;
    }

    /**
     * Process a filter state and return normalized value.
     */
    protected function processFilterState(string $filterName, mixed $filterState): mixed
    {
        if (! is_array($filterState)) {
            return (! is_null($filterState) && $filterState !== '' && $filterState !== [])
                ? $filterState
                : null;
        }

        // Handle 'values' key (multi-select filters)
        if (isset($filterState['values']) && ! empty($filterState['values'])) {
            return $filterState['values'];
        }

        // Handle 'value' key (single select filters)
        if (isset($filterState['value']) && ! is_null($filterState['value']) && $filterState['value'] !== '') {
            return $filterState['value'];
        }

        // Handle date range filters
        if (isset($filterState['created_from']) || isset($filterState['created_until'])) {
            if (! is_null($filterState['created_from'] ?? null) || ! is_null($filterState['created_until'] ?? null)) {
                return [
                    'from' => $filterState['created_from'] ?? null,
                    'until' => $filterState['created_until'] ?? null,
                ];
            }
        }

        // Handle generic from/until structure
        if (isset($filterState['from']) || isset($filterState['until'])) {
            if (! is_null($filterState['from'] ?? null) || ! is_null($filterState['until'] ?? null)) {
                return [
                    'from' => $filterState['from'] ?? null,
                    'until' => $filterState['until'] ?? null,
                ];
            }
        }

        // Handle other array structures - filter out empty values
        $filtered = array_filter($filterState, function ($value) {
            return ! is_null($value) && $value !== '' && $value !== [];
        });

        return ! empty($filtered) ? $filtered : null;
    }

    /**
     * Extract filters using fallback static filter names.
     *
     * @return array<string, mixed>
     */
    protected function extractFallbackFilters(): array
    {
        $activeFilters = [];
        $staticFilterNames = [
            'created_at', 'updated_at', 'cliente_id', 'numero_contador',
            'estado_pagamento', 'estado_leitura', 'mes_referencia', 'ano_referencia',
        ];

        foreach ($staticFilterNames as $filterName) {
            try {
                $filterState = $this->getTableFilterState($filterName);
                if (! empty($filterState) && $filterState !== '' && $filterState !== []) {
                    $activeFilters[$filterName] = $filterState;
                }
            } catch (\Exception $e) {
                // Continue to next filter
            }
        }

        return $activeFilters;
    }

    /**
     * Apply filters to the export query.
     */
    protected function applyFiltersToQuery(Builder $query, array $activeFilters): void
    {
        foreach ($activeFilters as $filterName => $filterValue) {
            $this->applySpecificFilter($query, $filterName, $filterValue);
        }
    }

    /**
     * Apply a specific filter to the query.
     */
    protected function applySpecificFilter(Builder $query, string $filterName, mixed $filterValue): void
    {
        $defaultFilters = $this->getExportConfig()->getDefaultFilters();

        if ($filterName === 'created_at') {
            $this->applyDateFilter($query, 'created_at', $filterValue);

            return;
        }

        if ($filterName === 'updated_at') {
            $this->applyDateFilter($query, 'updated_at', $filterValue);

            return;
        }

        if ($filterName === 'created_by') {
            if (! empty($filterValue) && (is_string($filterValue) || is_numeric($filterValue))) {
                $query->where('created_by', $filterValue);
            }

            return;
        }

        // Delegate to custom filter handler
        $this->applyCustomFilter($query, $filterName, $filterValue);
    }

    /**
     * Apply a date filter to the query.
     */
    protected function applyDateFilter(Builder $query, string $column, mixed $filterValue): void
    {
        if (! is_array($filterValue)) {
            return;
        }

        if (isset($filterValue['from']) && $filterValue['from']) {
            $query->whereDate($column, '>=', $filterValue['from']);
        }

        if (isset($filterValue['until']) && $filterValue['until']) {
            $query->whereDate($column, '<=', $filterValue['until']);
        }
    }

    /**
     * Apply custom filters to the query.
     *
     * Override this method in your ListRecords class to handle
     * resource-specific filters.
     */
    protected function applyCustomFilter(Builder $query, string $filterName, mixed $filterValue): void
    {
        // Override this method in the class using the trait to handle specific filters
    }

    /**
     * Normalize filter values for use in queries.
     *
     * @return array<mixed>
     */
    protected function normalizeFilterValue(mixed $value): array
    {
        if (is_array($value) && ! $this->isAssociativeArray($value)) {
            return array_filter($value, function (mixed $item): bool {
                return ! is_null($item) && $item !== '';
            });
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $val) {
                if (! is_null($val) && $val !== '') {
                    if (is_array($val)) {
                        $result = array_merge($result, array_filter($val));
                    } else {
                        $result[] = $val;
                    }
                }
            }

            return array_unique(array_filter($result));
        }

        if (! is_null($value) && $value !== '') {
            return [$value];
        }

        return [];
    }

    /**
     * Check if an array is associative.
     */
    protected function isAssociativeArray(mixed $array): bool
    {
        if (! is_array($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
