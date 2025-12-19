<?php

namespace Filament\AdvancedExport\Concerns;

use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Provides notification methods for export functionality.
 */
trait HasExportNotifications
{
    /**
     * Show notification when no data is found for export.
     */
    protected function showNoDataNotification(): void
    {
        if (! $this->getExportConfig()->shouldShowNoDataNotification()) {
            return;
        }

        Notification::make()
            ->title(__('advanced-export::messages.notifications.no_data.title'))
            ->body(__('advanced-export::messages.notifications.no_data.body'))
            ->warning()
            ->send();
    }

    /**
     * Show success notification after export.
     */
    protected function showExportSuccessNotification(int $count): void
    {
        if (! $this->getExportConfig()->shouldShowSuccessNotification()) {
            return;
        }

        Notification::make()
            ->title(__('advanced-export::messages.notifications.success.title'))
            ->body(__('advanced-export::messages.notifications.success.body', ['count' => $count]))
            ->success()
            ->send();
    }

    /**
     * Show notification when export is queued for background processing.
     */
    protected function showQueuedNotification(): void
    {
        Notification::make()
            ->title(__('advanced-export::messages.notifications.queued.title'))
            ->body(__('advanced-export::messages.notifications.queued.body'))
            ->info()
            ->send();
    }

    /**
     * Handle export errors with logging and notification.
     */
    protected function handleExportError(Exception $e, string $context): void
    {
        Log::error("Export error in {$context}: ".$e->getMessage(), [
            'exception' => $e,
            'context' => $context,
        ]);

        if (! $this->getExportConfig()->shouldShowErrorNotification()) {
            return;
        }

        Notification::make()
            ->title(__('advanced-export::messages.notifications.error.title'))
            ->body(__('advanced-export::messages.notifications.error.body', [
                'message' => $e->getMessage(),
            ]))
            ->danger()
            ->send();
    }
}
