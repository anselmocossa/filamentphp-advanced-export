<?php

namespace Filament\AdvancedExport\Concerns;

use Filament\AdvancedExport\Contracts\Exportable;
use Filament\AdvancedExport\Support\ExportConfig;

/**
 * Provides configuration methods for export functionality.
 */
trait HasExportConfiguration
{
    /**
     * Get the configuration helper instance.
     */
    protected function getExportConfig(): ExportConfig
    {
        return app(ExportConfig::class);
    }

    /**
     * Get the model class for export.
     */
    protected function getExportModel(): string
    {
        return static::$resource::getModel();
    }

    /**
     * Get the available columns for export.
     *
     * @return array<string, string>
     */
    protected function getExportColumns(): array
    {
        $modelClass = $this->getExportModel();

        if (method_exists($modelClass, 'getExportColumns')) {
            return $modelClass::getExportColumns();
        }

        return $this->getExportConfig()->getFallbackColumns();
    }

    /**
     * Get the default columns for the export form.
     *
     * @return array<array{field: string, title: string}>
     */
    protected function getDefaultExportColumns(): array
    {
        $modelClass = $this->getExportModel();

        if (method_exists($modelClass, 'getDefaultExportColumns')) {
            return $modelClass::getDefaultExportColumns();
        }

        // Fallback: return first N columns as default
        $availableColumns = $this->getExportColumns();
        $defaultColumns = [];
        $maxDefault = $this->getExportConfig()->getMaxDefaultColumns();

        $count = 0;
        foreach ($availableColumns as $field => $title) {
            if ($count >= $maxDefault) {
                break;
            }
            $defaultColumns[] = [
                'field' => $field,
                'title' => $title,
            ];
            $count++;
        }

        return $defaultColumns;
    }

    /**
     * Get the view name for simple export.
     */
    protected function getExportViewName(): string
    {
        $config = $this->getExportConfig();

        if ($config->usePackageViews()) {
            return 'advanced-export::exports.default-simple';
        }

        $modelClass = $this->getExportModel();
        $tableName = (new $modelClass)->getTable();
        $path = $config->getViewPath();
        $suffix = $config->getSimpleSuffix();

        return "{$path}.{$tableName}{$suffix}";
    }

    /**
     * Get the view name for advanced export.
     */
    protected function getAdvancedExportViewName(): string
    {
        $config = $this->getExportConfig();

        if ($config->usePackageViews()) {
            return 'advanced-export::exports.default-advanced';
        }

        $modelClass = $this->getExportModel();
        $tableName = (new $modelClass)->getTable();
        $path = $config->getViewPath();
        $suffix = $config->getAdvancedSuffix();

        return "{$path}.{$tableName}{$suffix}";
    }

    /**
     * Get the maximum number of records to export.
     */
    protected function getExportLimit(): int
    {
        return $this->getExportConfig()->getMaxRecords();
    }

    /**
     * Get the date format for exports.
     */
    protected function getExportDateFormat(): string
    {
        return $this->getExportConfig()->getDateFormat();
    }

    /**
     * Get the chunk size for processing.
     */
    protected function getExportChunkSize(): int
    {
        return $this->getExportConfig()->getChunkSize();
    }

    /**
     * Get the action name for the export button.
     */
    protected function getExportActionName(): string
    {
        return $this->getExportConfig()->getActionName();
    }

    /**
     * Get the action label for the export button.
     */
    protected function getExportActionLabel(): string
    {
        $label = $this->getExportConfig()->getActionLabel();

        return $label ?? __('advanced-export::messages.action.label');
    }

    /**
     * Get the action icon for the export button.
     */
    protected function getExportActionIcon(): string
    {
        return $this->getExportConfig()->getActionIcon();
    }

    /**
     * Get the action color for the export button.
     */
    protected function getExportActionColor(): string
    {
        return $this->getExportConfig()->getActionColor();
    }

    /**
     * Get the modal heading text.
     */
    protected function getExportModalHeading(): string
    {
        return config('advanced-export.action.modal_heading')
            ?? __('advanced-export::messages.modal.heading');
    }

    /**
     * Get the modal description text.
     */
    protected function getExportModalDescription(): string
    {
        return config('advanced-export.action.modal_description')
            ?? __('advanced-export::messages.modal.description', [
                'limit' => $this->getExportLimit(),
            ]);
    }

    /**
     * Get the modal submit button label.
     */
    protected function getExportModalSubmitLabel(): string
    {
        return config('advanced-export.action.modal_submit_label')
            ?? __('advanced-export::messages.modal.submit');
    }
}
