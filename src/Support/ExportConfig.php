<?php

namespace Filament\AdvancedExport\Support;

class ExportConfig
{
    public function getMaxRecords(): int
    {
        return config('advanced-export.limits.max_records', 2000);
    }

    public function getChunkSize(): int
    {
        return config('advanced-export.limits.chunk_size', 500);
    }

    public function getQueueThreshold(): int
    {
        return config('advanced-export.limits.queue_threshold', 2000);
    }

    public function getViewPath(): string
    {
        return config('advanced-export.views.path', 'exports');
    }

    public function getSimpleSuffix(): string
    {
        return config('advanced-export.views.simple_suffix', '-excel');
    }

    public function getAdvancedSuffix(): string
    {
        return config('advanced-export.views.advanced_suffix', '-excel-advanced');
    }

    public function usePackageViews(): bool
    {
        return config('advanced-export.views.use_package_views', false);
    }

    public function getDateFormat(): string
    {
        return config('advanced-export.date_format', 'd/m/Y H:i');
    }

    public function getDateOnlyFormat(): string
    {
        return config('advanced-export.date_only_format', 'd/m/Y');
    }

    public function getFileExtension(): string
    {
        return config('advanced-export.file.extension', 'xlsx');
    }

    public function getFileDisk(): string
    {
        return config('advanced-export.file.disk', 'public');
    }

    public function getFileDirectory(): string
    {
        return config('advanced-export.file.directory', 'exports');
    }

    public function getMaxSelectableColumns(): int
    {
        return config('advanced-export.columns.max_selectable', 20);
    }

    public function getMinRequiredColumns(): int
    {
        return config('advanced-export.columns.min_required', 1);
    }

    public function getMaxDefaultColumns(): int
    {
        return config('advanced-export.columns.max_default', 5);
    }

    public function getActionName(): string
    {
        return config('advanced-export.action.name', 'export');
    }

    public function getActionLabel(): ?string
    {
        return config('advanced-export.action.label');
    }

    public function getActionIcon(): string
    {
        return config('advanced-export.action.icon', 'heroicon-o-arrow-down-tray');
    }

    public function getActionColor(): string
    {
        return config('advanced-export.action.color', 'success');
    }

    public function getFallbackColumns(): array
    {
        return config('advanced-export.fallback_columns', [
            'id' => 'ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ]);
    }

    public function getDefaultFilters(): array
    {
        return config('advanced-export.default_filters', [
            'created_at',
            'updated_at',
            'created_by',
        ]);
    }

    public function isQueueEnabled(): bool
    {
        return config('advanced-export.queue.enabled', true);
    }

    public function getQueueConnection(): string
    {
        return config('advanced-export.queue.connection', 'default');
    }

    public function getQueueName(): string
    {
        return config('advanced-export.queue.queue', 'exports');
    }

    public function shouldShowSuccessNotification(): bool
    {
        return config('advanced-export.notifications.show_success', true);
    }

    public function shouldShowNoDataNotification(): bool
    {
        return config('advanced-export.notifications.show_no_data', true);
    }

    public function shouldShowErrorNotification(): bool
    {
        return config('advanced-export.notifications.show_errors', true);
    }
}
