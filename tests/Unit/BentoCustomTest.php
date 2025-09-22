<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../inc/custom.php';

beforeEach(function () {
    wp_test_reset_state();
    $_POST = [];
});

test('Bento custom enqueues tracking script when site key and tracking enabled', function () {
    global $__wp_test_state;
    $__wp_test_state['options']['bento_settings'] = [
        'bento_site_key' => 'site-abc',
        'bento_enable_tracking' => '1',
    ];
    $__wp_test_state['enqueued_scripts'] = [];
    $__wp_test_state['localized_scripts'] = [];

    $custom = new \Bento_Custom();
    $custom->scripts_styles();

    expect($__wp_test_state['enqueued_scripts'])->not->toBeEmpty();
    $script = $__wp_test_state['enqueued_scripts'][0];
    expect($script['handle'])->toBe('bento-js');
    expect($script['src'])->toBe('https://app.bentonow.com/site-abc.js?woocommerce=1');
    expect($__wp_test_state['localized_scripts'][0]['object_name'])->toBe('bento_wordpress_sdk_params');
});

test('Bento custom skips tracking enqueue when tracking disabled', function () {
    global $__wp_test_state;
    $__wp_test_state['options']['bento_settings'] = [
        'bento_site_key' => 'site-abc',
        'bento_enable_tracking' => '0',
    ];
    $__wp_test_state['enqueued_scripts'] = [];

    $custom = new \Bento_Custom();
    $custom->scripts_styles();

    expect($__wp_test_state['enqueued_scripts'])->toBeEmpty();
});

test('Bento custom skips tracking enqueue when site key missing', function () {
    global $__wp_test_state;
    $__wp_test_state['options']['bento_settings'] = [
        'bento_site_key' => '',
        'bento_enable_tracking' => '1',
    ];
    $__wp_test_state['enqueued_scripts'] = [];

    $custom = new \Bento_Custom();
    $custom->scripts_styles();

    expect($__wp_test_state['enqueued_scripts'])->toBeEmpty();
});
