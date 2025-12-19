<?php

namespace Filament\AdvancedExport;

use Filament\AdvancedExport\Commands\GenerateModelMethodsCommand;
use Filament\AdvancedExport\Commands\GenerateViewsCommand;
use Filament\AdvancedExport\Commands\InstallCommand;
use Filament\AdvancedExport\Commands\PublishCommand;
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
    }
}
