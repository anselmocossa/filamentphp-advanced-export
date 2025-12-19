<?php

namespace Filament\AdvancedExport\Traits;

/**
 * Helper trait for models implementing the Exportable interface.
 *
 * Provides utility methods for working with export columns.
 */
trait InteractsWithExportable
{
    /**
     * Get the export columns as options array.
     *
     * @return array<string, string>
     */
    public static function getExportColumnsAsOptions(): array
    {
        return static::getExportColumns();
    }

    /**
     * Get only the field names from export columns.
     *
     * @return array<string>
     */
    public static function getExportColumnFields(): array
    {
        return array_keys(static::getExportColumns());
    }

    /**
     * Get only the titles from export columns.
     *
     * @return array<string>
     */
    public static function getExportColumnTitles(): array
    {
        return array_values(static::getExportColumns());
    }

    /**
     * Check if a field is exportable.
     */
    public static function isExportableField(string $field): bool
    {
        return array_key_exists($field, static::getExportColumns());
    }

    /**
     * Get the title for a specific export field.
     */
    public static function getExportFieldTitle(string $field): ?string
    {
        return static::getExportColumns()[$field] ?? null;
    }
}
