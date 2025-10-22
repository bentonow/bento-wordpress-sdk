<?php

namespace Tests\Unit;

use Bento_Settings_Controller;
use Configuration_Interface;

require_once __DIR__ . '/../../inc/interfaces/mail-interfaces.php';
require_once __DIR__ . '/../../inc/class-bento-settings-controller.php';

class FakeConfiguration implements Configuration_Interface
{
    public array $options = [];
    public array $updatedOptions = [];
    public array $validatedCredentials = [];
    public bool $fetchAuthorsCalled = false;
    public array $fetchAuthorsResponse = ['success' => true, 'data' => []];

    public function get_option($key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    public function update_option($key, $value): bool
    {
        $this->options[$key] = $value;
        $this->updatedOptions[$key] = $value;
        return true;
    }

    public function validate_credentials($credentials): array
    {
        $this->validatedCredentials = $credentials;
        return ['success' => true, 'connection_status' => ['connected' => true]];
    }

    public function fetch_authors(): array
    {
        $this->fetchAuthorsCalled = true;
        return $this->fetchAuthorsResponse;
    }
}

beforeEach(function () {
    wp_test_reset_state();
    $_POST = [];
});

test('handle_update_settings fails when nonce check fails', function () {
    global $__wp_test_state;
    $__wp_test_state['fail_ajax_nonce'] = true;

    $config = new FakeConfiguration();
    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_update_settings();
    })->toThrow(\RuntimeException::class, 'wp_die: check_ajax_referer failed');

    expect($config->updatedOptions)->toBeEmpty();

    unset($__wp_test_state['fail_ajax_nonce']);
});

test('handle_update_settings denies users without capability', function () {
    global $__wp_test_state;
    $__wp_test_state['current_user_can'] = false;

    $_POST['key'] = 'option_key';
    $_POST['value'] = 'new-value';

    $config = new FakeConfiguration();
    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_update_settings();
    })->toThrow(\RuntimeException::class, 'wp_send_json_error');

    expect($__wp_test_state['json_error'][0]['message'])->toBe('Permission denied');
    expect($config->updatedOptions)->toBeEmpty();
});

test('handle_update_settings stores sanitized option value', function () {
    global $__wp_test_state;
    $_POST['key'] = 'option_key';
    $_POST['value'] = 'new-value';

    $config = new FakeConfiguration();
    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_update_settings();
    })->toThrow(\RuntimeException::class, 'wp_send_json');

    expect($config->updatedOptions)->toHaveKey('option_key');
    expect($config->updatedOptions['option_key'])->toBe('new-value');
    expect($__wp_test_state['json'][0]['success'])->toBeTrue();
});

test('handle_update_settings preserves connection status json payload', function () {
    global $__wp_test_state;
    $_POST['key'] = 'bento_connection_status';
    $_POST['value'] = wp_json_encode([
        'connected' => true,
        'message' => 'Connected',
        'code' => 200,
        'timestamp' => 1234567890,
    ]);

    $config = new FakeConfiguration();
    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_update_settings();
    })->toThrow(\RuntimeException::class, 'wp_send_json');

    expect($config->updatedOptions)->toHaveKey('bento_connection_status');
    expect($config->updatedOptions['bento_connection_status'])->toBe([
        'connected' => true,
        'message' => 'Connected',
        'code' => 200,
        'timestamp' => 1234567890,
    ]);
    expect($__wp_test_state['json'][0]['success'])->toBeTrue();
});

test('handle_update_settings rejects invalid connection status json', function () {
    global $__wp_test_state;
    $_POST['key'] = 'bento_connection_status';
    $_POST['value'] = '{invalid json}';

    $config = new FakeConfiguration();
    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_update_settings();
    })->toThrow(\RuntimeException::class, 'wp_send_json_error');

    expect($config->updatedOptions)->not->toHaveKey('bento_connection_status');
    expect($__wp_test_state['json_error'][0]['message'])->toBe('Invalid connection status payload');
});

test('handle_validate_connection updates credentials and returns response', function () {
    global $__wp_test_state;
    $_POST['site_key'] = 'site-123';
    $_POST['publishable_key'] = 'pub-abc';
    $_POST['secret_key'] = 'sec-xyz';

    $config = new FakeConfiguration();
    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_validate_connection();
    })->toThrow(\RuntimeException::class, 'wp_send_json');

    expect($config->updatedOptions)->toHaveKey('bento_site_key');
    expect($config->updatedOptions)->toHaveKey('bento_publishable_key');
    expect($config->updatedOptions)->toHaveKey('bento_secret_key');
    expect($config->fetchAuthorsCalled)->toBeTrue();
    expect($__wp_test_state['json'][0]['success'])->toBeTrue();
});

test('handle_fetch_authors rejects users without capability', function () {
    global $__wp_test_state;
    $__wp_test_state['current_user_can'] = false;

    $config = new FakeConfiguration();
    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_fetch_authors();
    })->toThrow(\RuntimeException::class, 'wp_send_json_error');

    expect($__wp_test_state['json_error'][0]['message'])->toBe('Permission denied');
    expect($config->fetchAuthorsCalled)->toBeFalse();
});

test('handle_fetch_authors returns data when authorized', function () {
    global $__wp_test_state;
    $config = new FakeConfiguration();
    $config->fetchAuthorsResponse = ['success' => true, 'authors' => ['alice']];

    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_fetch_authors();
    })->toThrow(\RuntimeException::class, 'wp_send_json');

    expect($config->fetchAuthorsCalled)->toBeTrue();
    expect($__wp_test_state['json'][0]['authors'])->toBe(['alice']);
});

test('handle_send_event_notification falls back to first author when no from email set', function () {
    global $__wp_test_state;
    $__wp_test_state['options']['bento_settings'] = [
        'bento_site_key' => 'site-key',
        'bento_publishable_key' => 'pub-key',
        'bento_secret_key' => 'sec-key',
    ];
    $__wp_test_state['remote_posts'] = [];

    $config = new FakeConfiguration();
    $config->fetchAuthorsResponse = [
        'success' => true,
        'data' => [
            'data' => [
                ['attributes' => ['email' => 'author@example.com']],
            ],
        ],
    ];

    $controller = new Bento_Settings_Controller($config);

    expect(function () use ($controller) {
        $controller->handle_send_event_notification();
    })->toThrow(\RuntimeException::class, 'wp_send_json_success');

    expect($config->fetchAuthorsCalled)->toBeTrue();
    expect($__wp_test_state['json_success'][0]['event']['email'])->toBe('author@example.com');
    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
});
