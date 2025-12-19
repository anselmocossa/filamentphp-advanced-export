# Filament Advanced Export

[![Latest Version on Packagist](https://img.shields.io/packagist/v/filamentphp/advanced-export.svg?style=flat-square)](https://packagist.org/packages/filamentphp/advanced-export)
[![Total Downloads](https://img.shields.io/packagist/dt/filamentphp/advanced-export.svg?style=flat-square)](https://packagist.org/packages/filamentphp/advanced-export)
[![License](https://img.shields.io/packagist/l/filamentphp/advanced-export.svg?style=flat-square)](https://packagist.org/packages/filamentphp/advanced-export)

Advanced export functionality for Filament resources with dynamic column selection, filtering, ordering, and background processing.

## Features

- **Dynamic Column Selection** - Users choose which columns to export
- **Custom Column Titles** - Rename columns in the exported file
- **Configurable Ordering** - Sort by any column, ascending or descending
- **Filter Support** - Respects active Filament table filters
- **Background Processing** - Queue large exports for async processing
- **View-Based Templates** - Customizable Blade views for export formatting
- **Bilingual Support** - English and Portuguese translations included
- **Artisan Commands** - Generate views and model methods automatically

## Requirements

- PHP 8.2+
- Laravel 11.0+ or 12.0+
- Filament 4.0+
- Maatwebsite Excel 3.1+

## Installation

Install the package via Composer:

```bash
composer require filamentphp/advanced-export
```

Run the installation command:

```bash
php artisan export:install
```

This will:
- Publish the configuration file
- Publish translation files
- Register the plugin in your panel (interactive)

### Manual Configuration

If you prefer manual setup, publish assets individually:

```bash
# Publish configuration
php artisan export:publish --config

# Publish views
php artisan export:publish --views

# Publish translations
php artisan export:publish --lang

# Publish stubs
php artisan export:publish --stubs

# Publish everything
php artisan export:publish --all
```

## Quick Start

### 1. Implement the Exportable Interface

Add the `Exportable` interface and required methods to your model:

```php
<?php

namespace App\Models;

use Filament\AdvancedExport\Contracts\Exportable;
use Filament\AdvancedExport\Traits\InteractsWithExportable;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model implements Exportable
{
    use InteractsWithExportable;

    public static function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'nome' => 'Name',
            'email' => 'Email',
            'telefone' => 'Phone',
            'estado' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function getDefaultExportColumns(): array
    {
        return [
            ['field' => 'id', 'title' => 'ID'],
            ['field' => 'nome', 'title' => 'Full Name'],
            ['field' => 'email', 'title' => 'Email Address'],
            ['field' => 'estado', 'title' => 'Status'],
            ['field' => 'created_at', 'title' => 'Registration Date'],
        ];
    }
}
```

Or use the Artisan command to generate methods automatically:

```bash
php artisan export:model App\\Models\\Cliente
```

### 2. Use the Trait in ListRecords

Add the `HasAdvancedExport` trait to your ListRecords page:

```php
<?php

namespace App\Filament\Resources\Cliente\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\AdvancedExport\Traits\HasAdvancedExport;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    use HasAdvancedExport;

    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getAdvancedExportHeaderAction(),
            CreateAction::make(),
        ];
    }
}
```

### 3. Generate Export Views

Generate the Blade views for your model:

```bash
php artisan export:views App\\Models\\Cliente
```

This creates two files:
- `resources/views/exports/clientes-excel.blade.php` (simple export)
- `resources/views/exports/clientes-excel-advanced.blade.php` (advanced export)

## Configuration

The configuration file is published to `config/advanced-export.php`:

```php
return [
    // Export limits
    'limits' => [
        'max_records' => 2000,
        'chunk_size' => 500,
        'queue_threshold' => 2000,
    ],

    // View configuration
    'views' => [
        'path' => 'exports',
        'simple_suffix' => '-excel',
        'advanced_suffix' => '-excel-advanced',
        'use_package_views' => false, // Use default package views
    ],

    // Date formatting
    'date_format' => 'd/m/Y H:i',

    // File generation
    'file' => [
        'extension' => 'xlsx',
        'disk' => 'public',
        'directory' => 'exports',
    ],

    // Action button appearance
    'action' => [
        'name' => 'export',
        'label' => null, // Uses translation
        'icon' => 'heroicon-o-arrow-down-tray',
        'color' => 'success',
    ],

    // Queue settings
    'queue' => [
        'enabled' => true,
        'connection' => 'default',
        'queue' => 'exports',
    ],
];
```

## Advanced Usage

### Custom Relationships

Load relationships for export:

```php
class ListClientes extends ListRecords
{
    use HasAdvancedExport;

    protected function getExportRelationshipsForModel(): array
    {
        return ['localizacoes', 'createdBy', 'tipoCliente'];
    }
}
```

### Custom Filter Handling

Handle resource-specific filters:

```php
class ListClientes extends ListRecords
{
    use HasAdvancedExport;

    protected function applyCustomFilter($query, string $filterName, mixed $filterValue): void
    {
        match ($filterName) {
            'tipo_cliente' => $query->where('tipo_cliente', $filterValue),
            'estado' => $query->where('estado', $filterValue),
            'provincia_id' => $query->whereIn('provincia_id', $this->normalizeFilterValue($filterValue)),
            default => null,
        };
    }
}
```

### Custom Ordering

Handle ordering by relationship columns:

```php
class ListClientes extends ListRecords
{
    use HasAdvancedExport;

    protected function applyCustomOrdering($query, string $orderColumn, string $orderDirection): void
    {
        if ($orderColumn === 'tipo_cliente_nome') {
            $query->join('tipo_clientes', 'clientes.tipo_cliente_id', '=', 'tipo_clientes.id')
                  ->orderBy('tipo_clientes.nome', $orderDirection);
            return;
        }

        $query->orderBy($orderColumn, $orderDirection);
    }
}
```

### Using Package Default Views

If you don't want to create custom views for each model:

```php
// config/advanced-export.php
'views' => [
    'use_package_views' => true,
],
```

## Artisan Commands

### `export:install`

Initial package setup:

```bash
php artisan export:install
php artisan export:install --panel=admin
php artisan export:install --no-interaction
```

### `export:views`

Generate export views for a model:

```bash
php artisan export:views App\\Models\\Cliente
php artisan export:views App\\Models\\Cliente --force
```

### `export:model`

Add export methods to a model:

```bash
php artisan export:model App\\Models\\Cliente
php artisan export:model App\\Models\\Cliente --columns=id,nome,email,created_at
php artisan export:model App\\Models\\Cliente --force
```

### `export:publish`

Publish package assets:

```bash
php artisan export:publish --config
php artisan export:publish --views
php artisan export:publish --stubs
php artisan export:publish --lang
php artisan export:publish --migrations
php artisan export:publish --all
php artisan export:publish --all --force
```

## Translations

The package includes translations for:
- English (`en`)
- Portuguese (`pt`)

To add more languages, publish the translations and create new language files:

```bash
php artisan export:publish --lang
```

Then create `resources/lang/vendor/advanced-export/{locale}/messages.php`.

## Background Processing

For exports exceeding the `queue_threshold` (default: 2000 records), you can use the `ProcessExportJob`:

```php
use Filament\AdvancedExport\Jobs\ProcessExportJob;

ProcessExportJob::dispatch(
    modelClass: Cliente::class,
    filters: $activeFilters,
    fileName: 'clientes_export_2024.xlsx',
    viewName: 'exports.clientes-excel-advanced',
    columnsConfig: $columnsConfig,
    orderColumn: 'created_at',
    orderDirection: 'desc',
    relationships: ['tipoCliente', 'provincia'],
    userId: auth()->id()
);
```

Make sure to run the queue worker:

```bash
php artisan queue:work --queue=exports
```

## Customizing the Export Button

Override trait methods to customize appearance:

```php
class ListClientes extends ListRecords
{
    use HasAdvancedExport;

    protected function getExportActionLabel(): string
    {
        return 'Download Excel';
    }

    protected function getExportActionIcon(): string
    {
        return 'heroicon-o-document-arrow-down';
    }

    protected function getExportActionColor(): string
    {
        return 'primary';
    }

    protected function getExportModalHeading(): string
    {
        return 'Configure Your Export';
    }
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@example.com instead of using the issue tracker.

## Credits

- [Anselmo Kossa](https://github.com/anselmokossa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
