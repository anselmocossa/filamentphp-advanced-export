<?php

namespace Filament\AdvancedExport\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'export:publish
                            {--config : Publish configuration file}
                            {--views : Publish view files}
                            {--stubs : Publish stub files}
                            {--lang : Publish translation files}
                            {--migrations : Publish migration files}
                            {--all : Publish all assets}
                            {--force : Overwrite existing files}';

    protected $description = 'Publish Advanced Export assets';

    public function handle(): int
    {
        $publishAll = $this->option('all');
        $force = $this->option('force');
        $published = false;

        if ($publishAll || $this->option('config')) {
            $this->call('vendor:publish', [
                '--tag' => 'advanced-export-config',
                '--force' => $force,
            ]);
            $published = true;
        }

        if ($publishAll || $this->option('views')) {
            $this->call('vendor:publish', [
                '--tag' => 'advanced-export-views',
                '--force' => $force,
            ]);
            $published = true;
        }

        if ($publishAll || $this->option('stubs')) {
            $this->call('vendor:publish', [
                '--tag' => 'advanced-export-stubs',
                '--force' => $force,
            ]);
            $published = true;
        }

        if ($publishAll || $this->option('lang')) {
            $this->call('vendor:publish', [
                '--tag' => 'advanced-export-translations',
                '--force' => $force,
            ]);
            $published = true;
        }

        if ($publishAll || $this->option('migrations')) {
            $this->call('vendor:publish', [
                '--tag' => 'advanced-export-migrations',
                '--force' => $force,
            ]);
            $published = true;
        }

        if (! $published) {
            $this->components->warn('No assets selected. Use --all or specific options.');
            $this->newLine();
            $this->line('Available options:');
            $this->line('  --config      Publish configuration file');
            $this->line('  --views       Publish view files');
            $this->line('  --stubs       Publish stub files');
            $this->line('  --lang        Publish translation files');
            $this->line('  --migrations  Publish migration files');
            $this->line('  --all         Publish all assets');
            $this->line('  --force       Overwrite existing files');

            return self::FAILURE;
        }

        $this->components->info('Assets published successfully!');

        return self::SUCCESS;
    }
}
