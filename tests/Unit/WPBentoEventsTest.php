<?php

namespace Tests\Unit;

use WP_Bento_Events;
use Bento_Events_Controller;

beforeEach(function () {
    wp_test_reset_state();

    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, []);
});

afterEach(function () {
    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, []);
});

test('WP Bento login verification triggers reminder event', function () {
    global $__wp_test_state;

    update_option('bento_settings', [
        'bento_site_key' => 'site-key',
        'bento_publishable_key' => 'pub-key',
        'bento_secret_key' => 'secret-key',
        'bento_events_user_not_logged' => 7,
        'bento_events_repeat_not_event' => 30,
    ]);

    $__wp_test_state['users']['list'] = [
        (object) ['ID' => 15, 'user_email' => 'inactive@example.com'],
    ];

    $__wp_test_state['user_meta'][15][WP_Bento_Events::BENTO_LAST_LOGIN_META_KEY] = strtotime('-10 day');

    $__wp_test_state['remote_posts'] = [];

    WP_Bento_Events::bento_verify_logins_hook();

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('user_not_logged_since');
    expect($event['email'])->toBe('inactive@example.com');
});

test('WP Bento login verification respects repeat window', function () {
    global $__wp_test_state;

    update_option('bento_settings', [
        'bento_site_key' => 'site-key',
        'bento_publishable_key' => 'pub-key',
        'bento_secret_key' => 'secret-key',
        'bento_events_user_not_logged' => 7,
        'bento_events_repeat_not_event' => 30,
    ]);

    $__wp_test_state['users']['list'] = [
        (object) ['ID' => 42, 'user_email' => 'recent@example.com'],
    ];

    $__wp_test_state['user_meta'][42][WP_Bento_Events::BENTO_LAST_LOGIN_META_KEY] = strtotime('-10 day');
    $__wp_test_state['user_meta'][42][WP_Bento_Events::BENTO_LAST_LOGIN_EVENT_SENT_META_KEY] = strtotime('-5 day');

    $__wp_test_state['remote_posts'] = [];

    WP_Bento_Events::bento_verify_logins_hook();

    expect($__wp_test_state['remote_posts'])->toBeEmpty();
});
