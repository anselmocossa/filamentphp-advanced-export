<?php

namespace Filament\AdvancedExport;

use Filament\AdvancedExport\Commands\GenerateModelMethodsCommand;
use Filament\AdvancedExport\Commands\GenerateViewsCommand;
use Filament\AdvancedExport\Commands\InstallCommand;
use Filament\AdvancedExport\Commands\PublishCommand;
use Filament\AdvancedExport\Commands\SetupResourceExportCommand;
use Filament\AdvancedExport\Concerns\HasExportPermission;
use Filament\Facades\Filament;
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
        // Only if Shield is installed
        if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShield::class)) {
            return;
        }

        try {
            $panels = Filament::getPanels();
        } catch (\Throwable) {
            return;
        }

        $manage = config('filament-shield.resources.manage', []);

        foreach ($panels as $panel) {
            foreach ($panel->getResources() as $resource) {
                if (! $this->usesExportPermissionTrait($resource)) {
                    continue;
                }

                // Get existing manage methods for this resource, or empty
                $methods = $manage[$resource] ?? [];

                // Add 'export' if not already present
                if (! in_array('export', $methods)) {
                    $methods[] = 'export';
                }

                $manage[$resource] = $methods;
            }
        }

        config(['filament-shield.resources.manage' => $manage]);

        // Also add 'export' to single_parameter_methods (export doesn't need a model instance)
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
