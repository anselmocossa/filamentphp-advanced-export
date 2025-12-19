<?php

namespace Filament\AdvancedExport\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Filament\AdvancedExport\Support\ExportConfig
 */
class AdvancedExport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Filament\AdvancedExport\Support\ExportConfig::class;
    }
}
