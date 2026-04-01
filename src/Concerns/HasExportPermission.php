<?php

namespace Filament\AdvancedExport\Concerns;

/**
 * Trait for Filament Resources to register 'export' permission with Shield.
 *
 * When a Resource uses this trait, FilamentShield will automatically
 * detect 'export' as a permission prefix and generate the corresponding
 * permission (e.g., Export:Titular, Export:InsurancePolicy) when running
 * `php artisan shield:generate`.
 *
 * @example
 * class TitularResource extends Resource
 * {
 *     use HasExportPermission;
 *     // Shield will generate: Export:Titular
 * }
 */
trait HasExportPermission
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'export',
        ];
    }
}
