<?php

/**
 * Tests for CSV Export support.
 *
 * HIGH FIX #5: Add CSV format option alongside XLSX.
 */

use Filament\AdvancedExport\Exports\CsvExport;
use Filament\AdvancedExport\Support\ExportConfig;

// --- HIGH #5: CSV Export support ---

test('CsvExport class exists', function () {
    expect(class_exists(CsvExport::class))->toBeTrue(
        'CsvExport class should exist at src/Exports/CsvExport.php'
    );
});

test('CsvExport implements FromCollection and WithHeadings', function () {
    $reflection = new ReflectionClass(CsvExport::class);

    expect($reflection->implementsInterface(\Maatwebsite\Excel\Concerns\FromCollection::class))->toBeTrue(
        'CsvExport should implement FromCollection'
    );
    expect($reflection->implementsInterface(\Maatwebsite\Excel\Concerns\WithHeadings::class))->toBeTrue(
        'CsvExport should implement WithHeadings'
    );
});

test('CsvExport returns correct headings from columns config', function () {
    $columnsConfig = [
        ['field' => 'id', 'title' => 'ID'],
        ['field' => 'name', 'title' => 'Full Name'],
        ['field' => 'email', 'title' => 'Email Address'],
    ];

    $records = collect([
        (object) ['id' => 1, 'name' => 'John', 'email' => 'john@test.com'],
    ]);

    $export = new CsvExport($records, $columnsConfig);

    expect($export->headings())->toBe(['ID', 'Full Name', 'Email Address']);
});

test('CsvExport returns correct collection data', function () {
    $columnsConfig = [
        ['field' => 'id', 'title' => 'ID'],
        ['field' => 'name', 'title' => 'Name'],
    ];

    $records = collect([
        (object) ['id' => 1, 'name' => 'John', 'email' => 'john@test.com'],
        (object) ['id' => 2, 'name' => 'Jane', 'email' => 'jane@test.com'],
    ]);

    $export = new CsvExport($records, $columnsConfig);
    $collection = $export->collection();

    expect($collection)->toHaveCount(2);
    expect($collection->first())->toBe([1, 'John']);
    expect($collection->last())->toBe([2, 'Jane']);
});

test('config file supports csv as format option', function () {
    config()->set('advanced-export.file.extension', 'csv');

    $config = new ExportConfig;
    expect($config->getFileExtension())->toBe('csv');
});

test('ExportConfig has getSupportedFormats method', function () {
    $config = new ExportConfig;

    expect(method_exists($config, 'getSupportedFormats'))->toBeTrue(
        'ExportConfig should have getSupportedFormats() method'
    );

    $formats = $config->getSupportedFormats();
    expect($formats)->toContain('xlsx');
    expect($formats)->toContain('csv');
});
