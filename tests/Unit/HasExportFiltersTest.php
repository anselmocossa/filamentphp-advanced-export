<?php

/**
 * Tests for HasExportFilters trait.
 *
 * CRITICAL FIX #1: Remove hardcoded project-specific filter names
 * from extractFallbackFilters(). The fallback should use configurable
 * filter names, not hardcoded ones like 'cliente_id', 'numero_contador', etc.
 */

use Filament\AdvancedExport\Support\ExportConfig;

// --- CRITICAL #1: Fallback filters must not contain project-specific names ---

test('extractFallbackFilters does not contain hardcoded project-specific filter names', function () {
    // These project-specific names should NOT be in the package
    $projectSpecificFilters = [
        'cliente_id',
        'numero_contador',
        'estado_pagamento',
        'estado_leitura',
        'mes_referencia',
        'ano_referencia',
    ];

    // Read the source file and check it doesn't contain these hardcoded values
    $sourceCode = file_get_contents(__DIR__.'/../../src/Concerns/HasExportFilters.php');

    foreach ($projectSpecificFilters as $filter) {
        expect($sourceCode)->not->toContain("'{$filter}'",
            "Source code contains hardcoded project-specific filter '{$filter}'"
        );
    }
});

test('extractFallbackFilters uses configurable filter names from config', function () {
    // The fallback filters should come from config('advanced-export.fallback_filters')
    // or from a method that can be overridden
    $sourceCode = file_get_contents(__DIR__.'/../../src/Concerns/HasExportFilters.php');

    // Should reference config or a configurable method, not a static array
    expect($sourceCode)->toContain('getFallbackFilterNames');
});

test('ExportConfig has getFallbackFilterNames method', function () {
    $config = new ExportConfig;

    expect(method_exists($config, 'getFallbackFilterNames'))->toBeTrue(
        'ExportConfig should have getFallbackFilterNames() method'
    );
});

test('fallback filter names are configurable via config file', function () {
    // Set custom fallback filters in config
    config()->set('advanced-export.fallback_filters', [
        'created_at',
        'updated_at',
        'custom_field',
    ]);

    $config = new ExportConfig;
    $filters = $config->getFallbackFilterNames();

    expect($filters)->toContain('custom_field');
    expect($filters)->not->toContain('cliente_id');
    expect($filters)->not->toContain('numero_contador');
});
