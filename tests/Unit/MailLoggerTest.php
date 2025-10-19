<?php

namespace Tests\Unit;

use Bento_Mail_Logger;
use Bento_Logger;

require_once __DIR__ . '/../../inc/class-bento-logger.php';
require_once __DIR__ . '/../../inc/class-bento-mail-logger.php';

function create_temp_log_path(): string
{
    $dir = sys_get_temp_dir() . '/bento-mail-logger-' . uniqid();
    $created = @mkdir($dir, 0755, true);

    if ($created === false && !is_dir($dir)) {
        $error = error_get_last() ?: [];
        $message = $error['message'] ?? 'unknown error';
        $type = $error['type'] ?? 'unknown type';

        throw new \RuntimeException(
            sprintf(
                'Failed to create mail logger temp directory at "%s": %s (type %s)',
                $dir,
                $message,
                $type
            )
        );
    }

    return $dir . '/mail-logs.json';
}

function cleanup_temp_log(string $path): void
{
    if (file_exists($path)) {
        unlink($path);
    }

    $dir = dirname($path);
    if (is_dir($dir)) {
        rmdir($dir);
    }
}

beforeEach(function () {
    wp_test_reset_state();
    $__wp_test_state = &$GLOBALS['__wp_test_state'];
    $__wp_test_state['options']['bento_settings'] = ['bento_enable_mail_logging' => '1'];
});

afterEach(function () {
    if (isset($this->tempLogPath)) {
        cleanup_temp_log($this->tempLogPath);
    }
});

test('log_mail persists entries when logging enabled', function () {
    $this->tempLogPath = create_temp_log_path();
    $logger = new Bento_Mail_Logger($this->tempLogPath);

    $logger->log_mail([
        'id' => 'entry-1',
        'type' => 'mail_received',
        'to' => 'test@example.com',
        'subject' => 'Test subject',
        'success' => true,
    ]);

    $logs = $logger->read_logs();
    expect($logs)->toHaveCount(1);
    expect($logs[0]['id'])->toBe('entry-1');
});

test('logging is skipped when disabled', function () {
    $GLOBALS['__wp_test_state']['options']['bento_settings'] = ['bento_enable_mail_logging' => '0'];
    $this->tempLogPath = create_temp_log_path();
    $logger = new Bento_Mail_Logger($this->tempLogPath);

    $logger->log_mail([
        'id' => 'entry-2',
        'type' => 'mail_received',
        'to' => 'test@example.com',
    ]);

    expect(file_exists($this->tempLogPath))->toBeFalse();
});

test('clear_logs removes content from log file', function () {
    $this->tempLogPath = create_temp_log_path();
    $logger = new Bento_Mail_Logger($this->tempLogPath);

    $logger->log_mail(['id' => 'entry-3', 'type' => 'mail_received']);
    expect(file_exists($this->tempLogPath))->toBeTrue();

    $logger->clear_logs();
    $logs = $logger->read_logs();
    expect($logs)->toBeEmpty();
});

test('check_size trims log history beyond limit', function () {
    $this->tempLogPath = create_temp_log_path();
    $logger = new Bento_Mail_Logger($this->tempLogPath);

    $reflection = new \ReflectionClass(Bento_Mail_Logger::class);
    $property = $reflection->getProperty('max_size');
    $property->setAccessible(true);
    $property->setValue($logger, 512);

    for ($i = 0; $i < 1100; $i++) {
        $logger->log_mail([
            'id' => 'entry-' . $i,
            'type' => 'mail_received',
            'subject' => 'Subject ' . $i,
            'timestamp' => time(),
            'payload' => str_repeat('A', 2048),
        ]);
    }

    $logs = $logger->read_logs();
    expect($logs)->toHaveCount(1000);
    expect($logs[0]['id'])->toBe('entry-1099');
});

test('is_duplicate detects duplicate entries within expiry window', function () {
    $this->tempLogPath = create_temp_log_path();
    $logger = new Bento_Mail_Logger($this->tempLogPath);

    $hash = md5('duplicate');
    $logger->log_mail([
        'id' => 'entry-dup',
        'type' => 'mail_received',
        'hash' => $hash,
        'timestamp' => time(),
    ]);

    expect($logger->is_duplicate($hash))->toBeTrue();
    expect($logger->is_duplicate(md5('other')))->toBeFalse();
});
