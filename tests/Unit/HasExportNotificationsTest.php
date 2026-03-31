<?php

/**
 * Tests for HasExportNotifications trait.
 *
 * CRITICAL FIX #4: handleExportError must NOT expose $e->getMessage()
 * to the user. It should show a generic message and log the full error.
 */

// --- CRITICAL #4: Error messages must not be exposed to user ---

test('handleExportError does not pass raw exception message to notification body', function () {
    // Read the source code of handleExportError method
    $sourceCode = file_get_contents(__DIR__.'/../../src/Concerns/HasExportNotifications.php');

    // Extract the handleExportError method specifically
    preg_match('/protected function handleExportError.*?^\s{4}\}/ms', $sourceCode, $matches);
    $methodBody = $matches[0] ?? '';

    // Find the Notification block within handleExportError
    preg_match('/Notification::make\(\).*?->send\(\)/s', $methodBody, $notifMatches);
    $notificationBlock = $notifMatches[0] ?? '';

    // The notification block should NOT reference getMessage
    // Log::error using getMessage is FINE (internal logging)
    // But Notification->body() with getMessage exposes sensitive info to user
    expect($notificationBlock)->not->toBeEmpty('handleExportError should have a Notification block');

    $hasGetMessage = str_contains($notificationBlock, 'getMessage');
    expect($hasGetMessage)->toBeFalse(
        'Notification body in handleExportError should not expose raw exception getMessage() to user'
    );
});

test('error notification EN body line does not expose raw exception message', function () {
    // Use absolute path from the package root
    $basePath = dirname(__DIR__, 2);
    $content = file_get_contents($basePath.'/resources/lang/en/messages.php');

    // Simple string search in the raw file content
    // The error notification body should NOT have :message
    $hasMessageInErrorBody = (bool) preg_match("/'error'\s*=>\s*\[\s*'title'.*?'body'\s*=>\s*'[^']*:message/s", $content);

    expect($hasMessageInErrorBody)->toBeFalse(
        'Error notification translation (EN) should not expose :message to user'
    );
});

test('error notification PT body line does not expose raw exception message', function () {
    $basePath = dirname(__DIR__, 2);
    $content = file_get_contents($basePath.'/resources/lang/pt/messages.php');

    $hasMessageInErrorBody = (bool) preg_match("/'error'\s*=>\s*\[\s*'title'.*?'body'\s*=>\s*'[^']*:message/s", $content);

    expect($hasMessageInErrorBody)->toBeFalse(
        'Error notification translation (PT) should not expose :message to user'
    );
});
