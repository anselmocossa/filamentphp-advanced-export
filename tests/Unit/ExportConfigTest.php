<?php

/**
 * Tests for ExportConfig class.
 *
 * Validates all configuration values are accessible and have proper defaults.
 */

use Filament\AdvancedExport\Support\ExportConfig;

test('ExportConfig returns correct default max records', function () {
    $config = new ExportConfig;
    expect($config->getMaxRecords())->toBe(2000);
});

test('ExportConfig returns correct default chunk size', function () {
    $config = new ExportConfig;
    expect($config->getChunkSize())->toBe(500);
});

test('ExportConfig returns correct default file extension', function () {
    $config = new ExportConfig;
    expect($config->getFileExtension())->toBe('xlsx');
});

test('ExportConfig respects config overrides', function () {
    config()->set('advanced-export.limits.max_records', 5000);

    $config = new ExportConfig;
    expect($config->getMaxRecords())->toBe(5000);
});

test('ExportConfig fallback columns have proper defaults', function () {
    $config = new ExportConfig;
    $columns = $config->getFallbackColumns();

    expect($columns)->toHaveKey('id');
    expect($columns)->toHaveKey('created_at');
    expect($columns)->toHaveKey('updated_at');
});

test('ExportConfig default filters are generic and not project-specific', function () {
    $config = new ExportConfig;
    $filters = $config->getDefaultFilters();

    // Should contain generic filters
    expect($filters)->toContain('created_at');
    expect($filters)->toContain('updated_at');

    // Should NOT contain project-specific filters
    expect($filters)->not->toContain('cliente_id');
    expect($filters)->not->toContain('numero_contador');
    expect($filters)->not->toContain('estado_pagamento');
});
