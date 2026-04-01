<?php

namespace Filament\AdvancedExport;

use Filament\AdvancedExport\Commands\GenerateModelMethodsCommand;
use Filament\AdvancedExport\Commands\GenerateViewsCommand;
use Filament\AdvancedExport\Commands\InstallCommand;
use Filament\AdvancedExport\Commands\PublishCommand;
use Filament\AdvancedExport\Commands\SetupResourceExportCommand;
use Filament\AdvancedExport\Concerns\HasExportPermission;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AdvancedExportServiceProvider extends PackageServiceProvider
{
    public static string $name = 'advanced-export';

    public static string $viewNamespace = 'advanced-export';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace)
            ->hasTranslations()
            ->hasMigration('create_export_jobs_table')
            ->hasCommands([
                InstallCommand::class,
                GenerateViewsCommand::class,
                GenerateModelMethodsCommand::class,
                PublishCommand::class,
                SetupResourceExportCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Publish stubs
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../stubs') => base_path('stubs/advanced-export'),
            ], "{$this->package->shortName()}-stubs");
        }

        // Auto-register 'export' permission in Shield's resources.manage
        // for any Resource that uses the HasExportPermission trait
        $this->registerExportPermissionsInShield();
    }

    /**
     * Detect Resources using HasExportPermission and register 'export'
     * in filament-shield.resources.manage config at runtime.
     *
     * This makes `php artisan shield:generate` create Export:{Resource}
     * permissions automatically — no manual config needed.
     */
    protected function registerExportPermissionsInShield(): void
    {
        if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShield::class)) {
            return;
        }

        $manage = config('filament-shield.resources.manage', []);

        // Scan Filament resource directories for classes using HasExportPermission
        $resourcePaths = config('filament.resources.path', app_path('Filament/Resources'));
        $paths = is_array($resourcePaths) ? $resourcePaths : [$resourcePaths];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Extract FQCN from file
                $content = file_get_contents($file->getPathname());
                if (! preg_match('/namespace\s+([\w\\\\]+)/', $content, $ns)) {
                    continue;
                }
                if (! preg_match('/class\s+(\w+)/', $content, $cls)) {
                    continue;
                }

                $fqcn = $ns[1] . '\\' . $cls[1];

                if (! class_exists($fqcn)) {
                    continue;
                }

                if (! $this->usesExportPermissionTrait($fqcn)) {
                    continue;
                }

                $methods = $manage[$fqcn] ?? [];
                if (! in_array('export', $methods)) {
                    $methods[] = 'export';
                }
                $manage[$fqcn] = $methods;
            }
        }

        config(['filament-shield.resources.manage' => $manage]);

        // Add 'export' to single_parameter_methods
        $singleParamMethods = config('filament-shield.policies.single_parameter_methods', []);
        if (! in_array('export', $singleParamMethods)) {
            $singleParamMethods[] = 'export';
            config(['filament-shield.policies.single_parameter_methods' => $singleParamMethods]);
        }
    }

    protected function usesExportPermissionTrait(string $resource): bool
    {
        return in_array(HasExportPermission::class, class_uses_recursive($resource));
    }
}
