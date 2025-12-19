<?php

namespace Filament\AdvancedExport\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

class GenerateModelMethodsCommand extends Command
{
    protected $signature = 'export:model
                            {model : The model class (e.g., App\\Models\\Cliente)}
                            {--columns= : Comma-separated list of columns}
                            {--force : Overwrite existing methods}';

    protected $description = 'Add export methods to a model';

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

        $reflection = new ReflectionClass($modelClass);
        $filePath = $reflection->getFileName();

        // Check if methods already exist
        $hasExportColumns = method_exists($modelClass, 'getExportColumns');
        $hasDefaultColumns = method_exists($modelClass, 'getDefaultExportColumns');

        if ($hasExportColumns && $hasDefaultColumns && ! $this->option('force')) {
            $this->components->warn('Export methods already exist in the model.');

            if (! confirm('Do you want to overwrite them?', false)) {
                return self::SUCCESS;
            }
        }

        // Get columns to export
        $columns = $this->getColumns($modelClass);

        if (empty($columns)) {
            $this->components->error('No columns selected.');

            return self::FAILURE;
        }

        // Generate method code
        $methodCode = $this->generateMethodCode($columns);

        // Add interface and methods to model
        $this->updateModel($filePath, $modelClass, $methodCode, $hasExportColumns || $hasDefaultColumns);

        $this->components->info('Export methods added to model successfully!');

        return self::SUCCESS;
    }

    protected function getColumns(string $modelClass): array
    {
        $columnsOption = $this->option('columns');

        if ($columnsOption) {
            $fields = explode(',', $columnsOption);
            $columns = [];
            foreach ($fields as $field) {
                $field = trim($field);
                $columns[$field] = Str::title(str_replace('_', ' ', $field));
            }

            return $columns;
        }

        // Get fillable and other attributes from model
        $model = new $modelClass;
        $fillable = $model->getFillable();
        $availableColumns = ['id' => 'ID'];

        foreach ($fillable as $field) {
            $availableColumns[$field] = Str::title(str_replace('_', ' ', $field));
        }

        $availableColumns['created_at'] = 'Created At';
        $availableColumns['updated_at'] = 'Updated At';

        // Interactive selection
        $selectedFields = multiselect(
            label: 'Select columns to include in exports',
            options: array_keys($availableColumns),
            default: array_keys($availableColumns),
            hint: 'Use space to select, enter to confirm'
        );

        $columns = [];
        foreach ($selectedFields as $field) {
            $columns[$field] = $availableColumns[$field];
        }

        return $columns;
    }

    protected function generateMethodCode(array $columns): string
    {
        $stubPath = $this->getStubPath('model-export-methods.stub');
        $stub = $this->files->get($stubPath);

        // Generate columns array
        $columnsCode = '';
        foreach ($columns as $field => $title) {
            $columnsCode .= "            '{$field}' => '{$title}',\n";
        }

        // Generate default columns array (first 5)
        $defaultColumnsCode = '';
        $count = 0;
        foreach ($columns as $field => $title) {
            if ($count >= 5) {
                break;
            }
            $defaultColumnsCode .= "            ['field' => '{$field}', 'title' => '{$title}'],\n";
            $count++;
        }

        return str_replace(
            ['{{COLUMNS}}', '{{DEFAULT_COLUMNS}}'],
            [rtrim($columnsCode), rtrim($defaultColumnsCode)],
            $stub
        );
    }

    protected function updateModel(string $filePath, string $modelClass, string $methodCode, bool $replaceExisting): void
    {
        $content = $this->files->get($filePath);

        // Add Exportable interface
        $interfaceImport = 'use Filament\\AdvancedExport\\Contracts\\Exportable;';
        $traitImport = 'use Filament\\AdvancedExport\\Traits\\InteractsWithExportable;';

        if (! Str::contains($content, $interfaceImport)) {
            $content = preg_replace(
                '/(namespace [^;]+;)/',
                "$1\n\n{$interfaceImport}\n{$traitImport}",
                $content
            );
        }

        // Add implements Exportable
        if (! Str::contains($content, 'implements Exportable')) {
            $content = preg_replace(
                '/(class\s+\w+\s+extends\s+\w+)(\s*\{)/s',
                '$1 implements Exportable$2',
                $content
            );

            // Also handle case where class already implements something
            $content = preg_replace(
                '/(class\s+\w+\s+extends\s+\w+\s+implements\s+)([^\{]+)(\{)/s',
                '$1$2, Exportable$3',
                $content
            );
        }

        // Add InteractsWithExportable trait
        if (! Str::contains($content, 'use InteractsWithExportable')) {
            $content = preg_replace(
                '/(class\s+\w+[^{]+\{)/',
                "$1\n    use InteractsWithExportable;\n",
                $content
            );
        }

        // Remove existing methods if replacing
        if ($replaceExisting) {
            $content = $this->removeExistingMethods($content);
        }

        // Add methods before the last closing brace
        $content = preg_replace(
            '/\}(\s*)$/',
            "\n{$methodCode}\n}$1",
            $content
        );

        $this->files->put($filePath, $content);
    }

    protected function removeExistingMethods(string $content): string
    {
        // Remove getExportColumns method
        $content = preg_replace(
            '/\n\s*\/\*\*[^*]*\*\/\s*public static function getExportColumns\(\)[^}]+\}[^}]+\}/s',
            '',
            $content
        );

        // Remove getDefaultExportColumns method
        $content = preg_replace(
            '/\n\s*\/\*\*[^*]*\*\/\s*public static function getDefaultExportColumns\(\)[^}]+\}[^}]+\}/s',
            '',
            $content
        );

        return $content;
    }

    protected function getStubPath(string $stub): string
    {
        $customPath = base_path("stubs/advanced-export/{$stub}");

        if ($this->files->exists($customPath)) {
            return $customPath;
        }

        return __DIR__."/../../stubs/{$stub}";
    }
}
