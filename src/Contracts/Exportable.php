<?php

namespace Filament\AdvancedExport\Contracts;

/**
 * Interface for models that support advanced export functionality.
 *
 * Implement this interface on your Eloquent models to enable
 * advanced export features with dynamic column selection.
 */
interface Exportable
{
    /**
     * Get all available columns for export.
     *
     * Returns an associative array where keys are field names
     * and values are display labels.
     *
     * @return array<string, string> Array of field => label pairs
     *
     * @example
     * return [
     *     'id' => 'ID',
     *     'name' => 'Name',
     *     'email' => 'Email Address',
     *     'created_at' => 'Created At',
     * ];
     */
    public static function getExportColumns(): array;

    /**
     * Get default columns selected in export form.
     *
     * Returns an array of column configurations that will be
     * pre-selected when the export modal opens.
     *
     * @return array<array{field: string, title: string}>
     *
     * @example
     * return [
     *     ['field' => 'id', 'title' => 'ID'],
     *     ['field' => 'name', 'title' => 'Full Name'],
     *     ['field' => 'email', 'title' => 'Email'],
     * ];
     */
    public static function getDefaultExportColumns(): array;
}
