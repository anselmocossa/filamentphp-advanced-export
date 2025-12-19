<?php

namespace Filament\AdvancedExport\Jobs;

use Filament\AdvancedExport\Exports\AdvancedExport;
use Filament\AdvancedExport\Exports\SimpleExport;
use Filament\AdvancedExport\Support\ExportConfig;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Job for processing large exports in the background.
 *
 * This job handles exports that exceed the synchronous threshold
 * and processes them in chunks to avoid memory issues.
 */
class ProcessExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<array{field: string, title: string}>|null  $columnsConfig
     * @param  array<string>  $relationships
     */
    public function __construct(
        protected string $modelClass,
        protected array $filters,
        protected string $fileName,
        protected string $viewName,
        protected ?array $columnsConfig = null,
        protected string $orderColumn = 'created_at',
        protected string $orderDirection = 'desc',
        protected array $relationships = [],
        protected ?int $userId = null
    ) {
        $this->onQueue(config('advanced-export.queue.queue', 'exports'));

        // Use the configured connection or fall back to the app's default queue connection
        $connection = config('advanced-export.queue.connection');
        if ($connection && $connection !== 'default') {
            $this->onConnection($connection);
        }
        // If connection is null or 'default', let Laravel use QUEUE_CONNECTION from .env
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $config = app(ExportConfig::class);
            $chunkSize = $config->getChunkSize();

            $query = $this->buildQuery();

            $totalRecords = $query->count();

            if ($totalRecords === 0) {
                Log::info("Export Job: No records found for {$this->modelClass}");

                return;
            }

            Log::info("Export Job: Processing {$totalRecords} records for {$this->modelClass}");

            // Collect all data
            $allData = collect();

            $query->orderBy($this->orderColumn, $this->orderDirection)
                ->chunk($chunkSize, function ($records) use (&$allData) {
                    $allData = $allData->merge($records);
                    Log::info('Export Job: Processed chunk with '.count($records).' records');
                });

            // Get table name for view data
            $tableName = (new $this->modelClass)->getTable();

            // Prepare view data
            $viewData = [
                $tableName => $allData,
            ];

            if ($this->columnsConfig !== null) {
                $viewData['columnsConfig'] = $this->columnsConfig;
                $export = new AdvancedExport($allData, $this->columnsConfig, $this->viewName, $viewData);
            } else {
                $export = new SimpleExport($allData, $this->viewName, $viewData);
            }

            // Store the file
            $directory = $config->getFileDirectory();
            $disk = $config->getFileDisk();
            $filePath = "{$directory}/{$this->fileName}";

            Excel::store($export, $filePath, $disk);

            Log::info("Export Job: Completed - {$filePath}");

            // Send notification to user with download link
            $this->sendCompletionNotification($filePath, $disk, $totalRecords);

        } catch (\Exception $e) {
            Log::error("Export Job Error for {$this->modelClass}: ".$e->getMessage(), [
                'exception' => $e,
                'model' => $this->modelClass,
                'fileName' => $this->fileName,
            ]);

            throw $e;
        }
    }

    /**
     * Build the query for export.
     */
    protected function buildQuery(): Builder
    {
        $query = $this->modelClass::query();

        if (! empty($this->relationships)) {
            $query->with($this->relationships);
        }

        $this->applyFilters($query);

        return $query;
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query): void
    {
        $model = $query->getModel();
        $table = $model->getTable();

        foreach ($this->filters as $filterName => $filterValue) {
            if (empty($filterValue)) {
                continue;
            }

            // Handle date filters
            if (in_array($filterName, ['created_at', 'updated_at'])) {
                if (is_array($filterValue)) {
                    if (isset($filterValue['from']) && $filterValue['from']) {
                        $query->whereDate($filterName, '>=', $filterValue['from']);
                    }
                    if (isset($filterValue['until']) && $filterValue['until']) {
                        $query->whereDate($filterName, '<=', $filterValue['until']);
                    }
                }

                continue;
            }

            // Handle created_by filter
            if ($filterName === 'created_by') {
                if (is_string($filterValue) || is_numeric($filterValue)) {
                    $query->where('created_by', $filterValue);
                }

                continue;
            }

            // Determine the actual column name
            // Check if filter is a relationship filter (filterName -> filterName_id)
            $columnName = $this->resolveColumnName($table, $filterName);
            if ($columnName === null) {
                Log::warning("Export Job: Column not found for filter '{$filterName}', skipping");

                continue;
            }

            // Handle array filters (whereIn)
            if (is_array($filterValue)) {
                $values = array_filter($filterValue, fn ($v) => ! is_null($v) && $v !== '');
                if (! empty($values)) {
                    $query->whereIn($columnName, $values);
                }

                continue;
            }

            // Handle simple value filters
            if (is_string($filterValue) || is_numeric($filterValue)) {
                $query->where($columnName, $filterValue);
            }
        }
    }

    /**
     * Resolve the actual column name for a filter.
     *
     * Handles relationship filters by checking for _id suffix.
     */
    protected function resolveColumnName(string $table, string $filterName): ?string
    {
        // First check if the filter name is a direct column
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, $filterName)) {
            return $filterName;
        }

        // Check if it's a relationship filter (filterName + '_id')
        $relationshipColumn = $filterName.'_id';
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, $relationshipColumn)) {
            return $relationshipColumn;
        }

        // Column doesn't exist
        return null;
    }

    /**
     * Send notification to user when export completes.
     */
    protected function sendCompletionNotification(string $filePath, string $disk, int $totalRecords): void
    {
        if (! $this->userId) {
            Log::info('Export Job: No user ID provided, skipping notification');

            return;
        }

        // Get the user model class from config or default to App\Models\User
        $userModel = config('advanced-export.user_model', 'App\\Models\\User');

        $user = $userModel::find($this->userId);

        if (! $user) {
            Log::warning("Export Job: User not found with ID {$this->userId}");

            return;
        }

        // Generate download URL
        $downloadUrl = Storage::disk($disk)->url($filePath);

        // Get translations
        $title = __('advanced-export::messages.notification.export_complete');
        $body = __('advanced-export::messages.notification.export_body', [
            'records' => number_format($totalRecords),
            'filename' => $this->fileName,
        ]);
        $downloadLabel = __('advanced-export::messages.notification.download');

        Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->actions([
                Action::make('download')
                    ->label($downloadLabel)
                    ->url($downloadUrl, shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->sendToDatabase($user);

        Log::info("Export Job: Notification sent to user {$this->userId}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Export Job Failed for {$this->modelClass}: ".$exception->getMessage(), [
            'exception' => $exception,
            'model' => $this->modelClass,
            'fileName' => $this->fileName,
        ]);

        // Send failure notification to user
        $this->sendFailureNotification($exception);
    }

    /**
     * Send notification to user when export fails.
     */
    protected function sendFailureNotification(\Throwable $exception): void
    {
        if (! $this->userId) {
            return;
        }

        $userModel = config('advanced-export.user_model', 'App\\Models\\User');
        $user = $userModel::find($this->userId);

        if (! $user) {
            return;
        }

        $title = __('advanced-export::messages.notification.export_failed');
        $body = __('advanced-export::messages.notification.export_failed_body', [
            'filename' => $this->fileName,
        ]);

        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->sendToDatabase($user);
    }
}
