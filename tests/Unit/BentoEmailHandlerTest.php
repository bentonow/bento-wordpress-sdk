<?php

namespace Tests\Unit;

use Bento_Email_Handler;

beforeEach(function () {
    wp_test_reset_state();
    require_once __DIR__ . '/../../bento-helper.php';
    require_once __DIR__ . '/../../inc/events-controllers/class_bento_email_handler.php';
});

test('Bento email handler skips hooks when transactional disabled', function () {
    update_option('bento_settings', ['bento_enable_transactional' => '0']);

    new Bento_Email_Handler();

    global $__wp_test_state;
    expect($__wp_test_state['actions'])->not->toHaveKey('wp_mail');
});

test('Bento email handler hooks into wp_mail when enabled', function () {
    update_option('bento_settings', [
        'bento_enable_transactional' => '1',
        'bento_site_key' => 'site',
        'bento_publishable_key' => 'pub',
        'bento_secret_key' => 'sec',
    ]);

    new Bento_Email_Handler();

    global $__wp_test_state;
    expect($__wp_test_state['actions'])->toHaveKey('wp_mail');
    expect($__wp_test_state['actions'])->toHaveKey('bento_process_email_queue');
    expect($__wp_test_state['scheduled_events'][0]['hook'])->toBe('bento_process_email_queue');
});

test('Bento email handler ignores emails with attachments', function () {
    update_option('bento_settings', [
        'bento_enable_transactional' => '1',
        'bento_site_key' => 'site',
        'bento_publishable_key' => 'pub',
        'bento_secret_key' => 'sec',
    ]);

    $handler = new Bento_Email_Handler();

    $queue_before = get_option('bento_email_queue', []);

    $original = [
        'to' => 'attach@example.com',
        'subject' => 'Subject',
        'message' => 'Body',
        'attachments' => ['file.pdf'],
    ];

    $result = $handler->intercept_wp_mail($original);

    expect($result)->toBe($original);
    $queue_after = get_option('bento_email_queue', []);
    expect($queue_after)->toBe($queue_before);
});

test('Bento email handler deduplicates queued messages', function () {
    update_option('bento_settings', [
        'bento_enable_transactional' => '1',
        'bento_site_key' => 'site',
        'bento_publishable_key' => 'pub',
        'bento_secret_key' => 'sec',
    ]);

    $handler = new Bento_Email_Handler();

    $email = [
        'to' => 'duplicate@example.com',
        'subject' => 'Subject',
        'message' => 'Body',
        'headers' => [],
        'attachments' => [],
    ];

    $handler->intercept_wp_mail($email);
    $handler->intercept_wp_mail($email);

    $queue = get_option('bento_email_queue', []);
    expect($queue)->toHaveCount(1);
});

test('Bento email handler processes queue and clears sent items', function () {
    update_option('bento_settings', [
        'bento_enable_transactional' => '1',
        'bento_site_key' => 'site',
        'bento_publishable_key' => 'pub',
        'bento_secret_key' => 'sec',
    ]);

    $handler = new Bento_Email_Handler();

    $email = [
        'to' => 'queue@example.com',
        'subject' => 'Queued',
        'message' => 'Processing',
        'headers' => [],
    ];

    update_option('bento_email_queue', [[
        'email_data' => $email,
        'timestamp' => time(),
        'hash' => md5('queue@example.com|Queued'),
    ]]);

    $handler->process_email_queue();

    $queue = get_option('bento_email_queue', []);
    expect($queue)->toBe([]);
});
