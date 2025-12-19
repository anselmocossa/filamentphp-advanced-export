<?php

namespace Filament\AdvancedExport\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;

use function Laravel\Prompts\confirm;

class SetupResourceExportCommand extends Command
{
    protected $signature = 'export:resource
                            {resource : The Filament resource class (e.g., App\\Filament\\Resources\\Payments\\PaymentResource)}
                            {--force : Overwrite existing files}';

    protected $description = 'Setup export for a Filament resource (configures model, generates views, and updates ListRecords page)';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle(): int
    {
        $resourceClass = $this->argument('resource');

        if (! class_exists($resourceClass)) {
            $this->components->error("Resource class '{$resourceClass}' not found.");

            return self::FAILURE;
        }

        // Get model from resource
        $modelClass = $this->getModelFromResource($resourceClass);
        if (! $modelClass) {
            $this->components->error("Could not determine model from resource '{$resourceClass}'.");

            return self::FAILURE;
        }

        $this->components->info("Resource: {$resourceClass}");
        $this->components->info("Model: {$modelClass}");

        // Get ListRecords page from resource
        $listPageClass = $this->getListPageFromResource($resourceClass);
        if (! $listPageClass) {
            $this->components->error("Could not find ListRecords page in resource '{$resourceClass}'.");

            return self::FAILURE;
        }

        $this->components->info("List Page: {$listPageClass}");

        // Step 1: Configure model
        $this->components->task('Configuring model export methods', function () use ($modelClass) {
            return $this->call('export:model', [
                'model' => $modelClass,
                '--force' => $this->option('force'),
            ]) === 0;
        });

        // Step 2: Generate views
        $this->components->task('Generating export views', function () use ($modelClass) {
            return $this->call('export:views', [
                'model' => $modelClass,
                '--force' => $this->option('force'),
            ]) === 0;
        });

        // Step 3: Update ListRecords page
        $this->components->task('Updating ListRecords page', function () use ($listPageClass) {
            return $this->updateListPage($listPageClass);
        });

        $this->newLine();
        $this->components->success('Export setup completed successfully!');

        return self::SUCCESS;
    }

    protected function getModelFromResource(string $resourceClass): ?string
    {
        if (! method_exists($resourceClass, 'getModel')) {
            // Try to get from static property
            $reflection = new ReflectionClass($resourceClass);
            $property = $reflection->getProperty('model');

            if ($property->isInitialized(null) === false) {
                // Check default value
                $defaultValue = $property->getDefaultValue();
                if ($defaultValue && class_exists($defaultValue)) {
                    return $defaultValue;
                }
            }

            return null;
        }

        return $resourceClass::getModel();
    }

    protected function getListPageFromResource(string $resourceClass): ?string
    {
        if (! method_exists($resourceClass, 'getPages')) {
            return null;
        }

        $pages = $resourceClass::getPages();

        if (! isset($pages['index'])) {
            return null;
        }

        // Get the page class from the route
        $indexPage = $pages['index'];

        // The page is a PageRegistration object, we need to get the class
        if (is_object($indexPage) && method_exists($indexPage, 'getPage')) {
            return $indexPage->getPage();
        }

        // Try reflection to get the page class
        if (is_object($indexPage)) {
            $reflection = new ReflectionClass($indexPage);
            $pageProperty = $reflection->getProperty('page');
            $pageProperty->setAccessible(true);

            return $pageProperty->getValue($indexPage);
        }

        return null;
    }

    protected function updateListPage(string $listPageClass): bool
    {
        $reflection = new ReflectionClass($listPageClass);
        $filePath = $reflection->getFileName();
        $content = $this->files->get($filePath);

        // Check if already has the trait
        if (Str::contains($content, 'HasAdvancedExport')) {
            $this->components->warn('ListRecords page already has HasAdvancedExport trait.');

            if (! $this->option('force') && ! confirm('Do you want to update it anyway?', false)) {
                return true;
            }
        }

        // Add import if needed
        if (! Str::contains($content, 'Filament\\AdvancedExport\\Traits\\HasAdvancedExport')) {
            // Find position of class declaration
            $classPos = strpos($content, "\nclass ");
            if ($classPos === false) {
                $classPos = strlen($content);
            }

            $beforeClass = substr($content, 0, $classPos);
            $lastImportPos = strrpos($beforeClass, "\nuse ");

            if ($lastImportPos !== false) {
                $endOfLine = strpos($content, "\n", $lastImportPos + 1);
                if ($endOfLine !== false) {
                    $import = "use Filament\\AdvancedExport\\Traits\\HasAdvancedExport;\n";
                    $content = substr($content, 0, $endOfLine + 1) . $import . substr($content, $endOfLine + 1);
                }
            }
        }

        // Add trait if needed - check if trait is used INSIDE the class (not the import statement)
        // The trait use statement must be after the class opening brace
        $classMatch = preg_match('/class\s+\w+[^{]+\{(.*)$/s', $content, $classContent);
        $hasTraitInClass = $classMatch && preg_match('/^\s*use\s+[^;]*HasAdvancedExport[^;]*;/m', $classContent[1]);

        if (! $hasTraitInClass) {
            // Check if class has existing traits (use statement right after class opening)
            if (preg_match('/(class\s+\w+[^{]+\{\s*)(use\s+[^;]+;)/s', $content)) {
                // Has existing traits, append
                $content = preg_replace(
                    '/(class\s+\w+[^{]+\{\s*)(use\s+)([^;]+)(;)/s',
                    '$1$2$3, HasAdvancedExport$4',
                    $content
                );
            } else {
                // No traits yet, add after class opening
                $content = preg_replace(
                    '/(class\s+\w+[^{]+\{)/',
                    "$1\n    use HasAdvancedExport;\n",
                    $content
                );
            }
        }

        // Add or update getHeaderActions method
        if (! Str::contains($content, 'getAdvancedExportHeaderAction')) {
            if (preg_match('/protected function getHeaderActions\(\).*?return\s*\[(.*?)\];/s', $content, $matches)) {
                // Has existing getHeaderActions, add our action
                $existingActions = trim($matches[1]);
                if (empty($existingActions)) {
                    $newActions = "\n            \$this->getAdvancedExportHeaderAction(),\n        ";
                } else {
                    $newActions = "\n            \$this->getAdvancedExportHeaderAction(),\n            " . trim($existingActions) . "\n        ";
                }
                $content = preg_replace(
                    '/(protected function getHeaderActions\(\).*?return\s*\[)(.*?)(\];)/s',
                    '$1' . $newActions . '$3',
                    $content
                );
            } else {
                // No getHeaderActions method, add it before the last closing brace
                $methodCode = <<<'PHP'

    protected function getHeaderActions(): array
    {
        return [
            $this->getAdvancedExportHeaderAction(),
        ];
    }
PHP;
                $content = preg_replace(
                    '/\}(\s*)$/',
                    $methodCode . "\n}\$1",
                    $content
                );
            }
        }

        $this->files->put($filePath, $content);

        return true;
    }
}
