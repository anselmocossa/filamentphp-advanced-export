<?php

namespace Filament\AdvancedExport\Concerns;

/**
 * Marker trait for Filament Resources to enable 'export' permission with Shield.
 *
 * When a Resource uses this trait, the plugin's ServiceProvider automatically
 * registers 'export' in Shield's `resources.manage` config at runtime.
 * When you run `php artisan shield:generate`, Shield creates the Export
 * permission for this resource (e.g., Export:Client) and adds an export()
 * method to the generated policy.
 *
 * Works with FilamentShield v4+. Without Shield installed, has no effect.
 *
 * @example
 * class TitularResource extends Resource
 * {
 *     use HasExportPermission;
 *     // After shield:generate → Export:Client permission created
 *     // Export button only visible to users with this permission
 * }
 */
trait HasExportPermission
{
    // Marker trait — detection handled by AdvancedExportServiceProvider
}
