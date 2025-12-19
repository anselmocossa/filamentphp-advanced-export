<?php

namespace Filament\AdvancedExport\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class InstallCommand extends Command
{
    protected $signature = 'export:install
                            {--panel= : The panel to install the plugin in}
                            {--no-interaction : Run without interaction}';

    protected $description = 'Install the Advanced Export package';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle(): int
    {
        $this->info('Installing Advanced Export...');

        // Publish configuration
        $this->publishConfig();

        // Publish translations
        $this->publishTranslations();

        // Optionally register plugin in panel
        if (! $this->option('no-interaction')) {
            $this->registerPlugin();
        }

        // Run migrations if needed
        if (! $this->option('no-interaction') && confirm('Do you want to run the migrations?', true)) {
            $this->call('migrate');
        }

        $this->components->info('Advanced Export installed successfully!');

        $this->newLine();
        $this->line('Next steps:');
        $this->line('1. Add the Exportable interface to your models');
        $this->line('2. Use the HasAdvancedExport trait in your ListRecords pages');
        $this->line('3. Generate export views with: php artisan export:views {model}');

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'advanced-export-config',
            '--force' => true,
        ]);

        $this->components->info('Configuration file published.');
    }

    protected function publishTranslations(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'advanced-export-translations',
            '--force' => true,
        ]);

        $this->components->info('Translation files published.');
    }

    protected function registerPlugin(): void
    {
        $panelProviders = $this->getPanelProviders();

        if (empty($panelProviders)) {
            $this->components->warn('No panel providers found.');

            return;
        }

        $panel = $this->option('panel');

        if (! $panel && count($panelProviders) === 1) {
            $panel = array_key_first($panelProviders);
        } elseif (! $panel) {
            $panel = select(
                label: 'Which panel would you like to register the plugin in?',
                options: array_keys($panelProviders),
            );
        }

        if (! isset($panelProviders[$panel])) {
            $this->components->error("Panel '{$panel}' not found.");

            return;
        }

        $providerPath = $panelProviders[$panel];
        $this->addPluginToProvider($providerPath);
    }

    protected function getPanelProviders(): array
    {
        $providers = [];
        $path = app_path('Providers/Filament');

        if (! is_dir($path)) {
            return $providers;
        }

        foreach ($this->files->files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = $this->files->get($file->getPathname());

            if (Str::contains($content, 'extends PanelProvider')) {
                $name = Str::beforeLast($file->getFilename(), 'PanelProvider.php');
                $name = $name ?: 'admin';
                $providers[strtolower($name)] = $file->getPathname();
            }
        }

        return $providers;
    }

    protected function addPluginToProvider(string $providerPath): void
    {
        $content = $this->files->get($providerPath);

        // Check if already registered
        if (Str::contains($content, 'AdvancedExportPlugin')) {
            $this->components->info('Plugin already registered in panel provider.');

            return;
        }

        // Add use statement
        $useStatement = 'use Filament\\AdvancedExport\\AdvancedExportPlugin;';

        if (! Str::contains($content, $useStatement)) {
            $content = preg_replace(
                '/(namespace [^;]+;)/',
                "$1\n\n{$useStatement}",
                $content
            );
        }

        // Add plugin to panel
        $content = preg_replace(
            '/(\->plugins\(\[)/',
            "$1\n                AdvancedExportPlugin::make(),",
            $content
        );

        // If no plugins method exists, add it before the end of panel()
        if (! Str::contains($content, '->plugins([')) {
            $content = preg_replace(
                '/(\$panel\s*\n\s*)(return \$panel;)/s',
                "$1->plugins([\n                AdvancedExportPlugin::make(),\n            ])\n            $2",
                $content
            );
        }

        $this->files->put($providerPath, $content);

        $this->components->info('Plugin registered in panel provider.');
    }
}
