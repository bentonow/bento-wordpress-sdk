<?php

namespace Tests\Unit;

use Bento_Mail_Admin;

function with_manifest(callable $callback) {
    $manifestDir = dirname(__DIR__, 2) . '/assets/build';
    if (!is_dir($manifestDir)) {
        if (!mkdir($manifestDir, 0755, true) && !is_dir($manifestDir)) {
            throw new \RuntimeException('Unable to create manifest directory: ' . $manifestDir);
        }
    }

    $manifestPath = $manifestDir . '/manifest.json';
    $original = file_exists($manifestPath) ? file_get_contents($manifestPath) : null;

    $manifest = [
        'assets/js/src/bento-app.jsx' => [
            'file' => 'app.js',
            'css' => ['app.css'],
        ],
    ];

    if (file_put_contents($manifestPath, json_encode($manifest)) === false) {
        throw new \RuntimeException('Failed to write manifest: ' . $manifestPath);
    }

    try {
        $callback($manifestPath);
    } finally {
        if ($original === null) {
            if (file_exists($manifestPath) && !unlink($manifestPath)) {
                throw new \RuntimeException('Failed to remove manifest: ' . $manifestPath);
            }
            $dirIterator = new \FilesystemIterator($manifestDir);
            if (!$dirIterator->valid() && !rmdir($manifestDir)) {
                throw new \RuntimeException('Failed to remove empty manifest directory: ' . $manifestDir);
            }
        } else {
            if (file_put_contents($manifestPath, $original) === false) {
                throw new \RuntimeException('Failed to restore manifest: ' . $manifestPath);
            }
        }
    }
}

beforeEach(function () {
    wp_test_reset_state();
    $_POST = [];
});

test('Mail admin does not enqueue assets without capability', function () {
    global $__wp_test_state;
    $__wp_test_state['current_user_can'] = false;

    $admin = new Bento_Mail_Admin();
    $admin->enqueue_scripts('bento_page_bento-mail-logs');

    expect($__wp_test_state['enqueued_scripts'])->toBeEmpty();
});

test('Mail admin enqueues app assets when manifest exists', function () {
    global $__wp_test_state;
    $__wp_test_state['current_user_can'] = true;

    with_manifest(function () {
        global $__wp_test_state;
        $admin = new Bento_Mail_Admin();
        $admin->enqueue_scripts('bento_page_bento-mail-logs');

        $handles = array_column($__wp_test_state['enqueued_scripts'], 'handle');
        expect($handles)->toContain('bento-admin-app');
        expect($__wp_test_state['localized_scripts'][0]['object_name'])->toBe('bentoAdmin');
    });
});

test('Mail admin registers submenu page', function () {
    $admin = new Bento_Mail_Admin();
    $admin->add_menu_page();

    global $__wp_test_state;
    expect($__wp_test_state['submenu_pages'][0]['menu_slug'])->toBe('bento-mail-logs');
});

test('Mail admin clears logs via AJAX', function () {
    global $__wp_test_state;
    $__wp_test_state['current_user_can'] = true;
    $__wp_test_state['doing_ajax'] = true;

    $admin = new Bento_Mail_Admin();

    try {
        $admin->clear_logs();
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('wp_send_json_success');
    }

    expect($__wp_test_state['json_success'][0]['message'])->toBe('Logs cleared successfully.');
});

test('Mail admin toggles logging option and redirects', function () {
    global $__wp_test_state;
    $__wp_test_state['current_user_can'] = true;
    $__wp_test_state['doing_ajax'] = false;
    $_POST['enable_logging'] = '1';

    $admin = new Bento_Mail_Admin();

    try {
        $admin->toggle_logging();
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('wp_safe_redirect');
    }

    $options = get_option('bento_settings');
    expect($options['bento_enable_mail_logging'])->toBeTrue();
    expect($__wp_test_state['redirects'][0]['location'])->toContain('page=bento-mail-logs');
});
