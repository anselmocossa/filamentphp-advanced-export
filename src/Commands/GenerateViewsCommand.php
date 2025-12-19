<?php

namespace Filament\AdvancedExport\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;

class GenerateViewsCommand extends Command
{
    protected $signature = 'export:views
                            {model : The model class (e.g., App\\Models\\Cliente)}
                            {--force : Overwrite existing views}';

    protected $description = 'Generate export views for a model';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle(): int
    {
        $modelClass = $this->argument('model');

        if (! class_exists($modelClass)) {
            $this->components->error("Model class '{$modelClass}' not found.");

            return self::FAILURE;
        }

        $model = new $modelClass;
        $tableName = $model->getTable();
        $variableName = Str::singular($tableName);

        // Get export columns if available
        $columns = method_exists($modelClass, 'getExportColumns')
            ? $modelClass::getExportColumns()
            : $this->getDefaultColumns($model);

        $viewPath = config('advanced-export.views.path', 'exports');
        $simpleSuffix = config('advanced-export.views.simple_suffix', '-excel');
        $advancedSuffix = config('advanced-export.views.advanced_suffix', '-excel-advanced');

        $simpleViewPath = resource_path("views/{$viewPath}/{$tableName}{$simpleSuffix}.blade.php");
        $advancedViewPath = resource_path("views/{$viewPath}/{$tableName}{$advancedSuffix}.blade.php");

        // Check if views already exist
        $force = $this->option('force');

        if ($this->files->exists($simpleViewPath) && ! $force) {
            if (! confirm("Simple view already exists. Overwrite?", false)) {
                $this->components->info('Skipping simple view.');
            } else {
                $this->generateSimpleView($simpleViewPath, $tableName, $variableName, $columns);
            }
        } else {
            $this->generateSimpleView($simpleViewPath, $tableName, $variableName, $columns);
        }

        if ($this->files->exists($advancedViewPath) && ! $force) {
            if (! confirm("Advanced view already exists. Overwrite?", false)) {
                $this->components->info('Skipping advanced view.');
            } else {
                $this->generateAdvancedView($advancedViewPath, $tableName, $variableName, $columns);
            }
        } else {
            $this->generateAdvancedView($advancedViewPath, $tableName, $variableName, $columns);
        }

        $this->components->info('Export views generated successfully!');

        return self::SUCCESS;
    }

    protected function getDefaultColumns($model): array
    {
        $fillable = $model->getFillable();
        $columns = ['id' => 'ID'];

        foreach ($fillable as $field) {
            $columns[$field] = Str::title(str_replace('_', ' ', $field));
        }

        $columns['created_at'] = 'Created At';
        $columns['updated_at'] = 'Updated At';

        return $columns;
    }

    protected function generateSimpleView(string $path, string $tableName, string $variableName, array $columns): void
    {
        $this->ensureDirectoryExists(dirname($path));

        $stubPath = $this->getStubPath('export-view-simple.stub');
        $stub = $this->files->get($stubPath);

        // Generate headers
        $headers = '';
        foreach ($columns as $field => $title) {
            $headers .= "        <th>{$title}</th>\n";
        }

        // Generate cells
        $cells = '';
        $dateFormat = config('advanced-export.date_format', 'd/m/Y H:i');

        foreach ($columns as $field => $title) {
            if (in_array($field, ['created_at', 'updated_at'])) {
                $cells .= "            <td>{{ \${$variableName}->{$field}?->format('{$dateFormat}') ?? '-' }}</td>\n";
            } else {
                $cells .= "            <td>{{ \${$variableName}->{$field} ?? '-' }}</td>\n";
            }
        }

        $content = str_replace(
            ['{{HEADERS}}', '{{CELLS}}', '{{TABLE_NAME}}', '{{VARIABLE_NAME}}'],
            [rtrim($headers), rtrim($cells), $tableName, $variableName],
            $stub
        );

        $this->files->put($path, $content);

        $this->components->info("Created: {$path}");
    }

    protected function generateAdvancedView(string $path, string $tableName, string $variableName, array $columns): void
    {
        $this->ensureDirectoryExists(dirname($path));

        $stubPath = $this->getStubPath('export-view-advanced.stub');
        $stub = $this->files->get($stubPath);

        // Generate switch cases
        $switchCases = '';
        $dateFormat = config('advanced-export.date_format', 'd/m/Y H:i');

        foreach ($columns as $field => $title) {
            $switchCases .= "                        @case('{$field}')\n";
            if (in_array($field, ['created_at', 'updated_at'])) {
                $switchCases .= "                            {{ \${$variableName}->{$field}?->format('{$dateFormat}') ?? '-' }}\n";
            } else {
                $switchCases .= "                            {{ \${$variableName}->{$field} ?? '-' }}\n";
            }
            $switchCases .= "                            @break\n";
        }

        $content = str_replace(
            ['{{SWITCH_CASES}}', '{{TABLE_NAME}}', '{{VARIABLE_NAME}}'],
            [rtrim($switchCases), $tableName, $variableName],
            $stub
        );

        $this->files->put($path, $content);

        $this->components->info("Created: {$path}");
    }

    protected function getStubPath(string $stub): string
    {
        $customPath = base_path("stubs/advanced-export/{$stub}");

        if ($this->files->exists($customPath)) {
            return $customPath;
        }

        return __DIR__."/../../stubs/{$stub}";
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }
}
