<?php

/**
 * Tests for record count in export modal.
 *
 * HIGH FIX #6: Show the number of records that will be exported
 * before the user clicks export.
 */

// --- HIGH #6: Record count in modal ---

test('HasAdvancedExport has getExportRecordCount method', function () {
    $sourceCode = file_get_contents(__DIR__.'/../../src/Traits/HasAdvancedExport.php');

    expect($sourceCode)->toContain('getExportRecordCount');
});

test('export form includes format selector', function () {
    $sourceCode = file_get_contents(__DIR__.'/../../src/Traits/HasAdvancedExport.php');

    expect($sourceCode)->toContain('export_format');
});

test('modal description translation includes record count placeholder', function () {
    $basePath = dirname(__DIR__, 2);
    $content = file_get_contents($basePath.'/resources/lang/en/messages.php');

    // Check the modal description line contains :count
    $hasCount = (bool) preg_match("/'description'\s*=>\s*'[^']*:count/s", $content);
    expect($hasCount)->toBeTrue('Modal description should include :count placeholder');
});
