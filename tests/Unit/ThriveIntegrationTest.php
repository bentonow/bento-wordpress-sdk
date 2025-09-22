<?php

namespace Tests\Unit;

use Bento_Events_Controller;

beforeEach(function () {
    wp_test_reset_state();

    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, [
        'bento_site_key' => 'site-key',
        'bento_publishable_key' => 'pub-key',
        'bento_secret_key' => 'secret-key',
    ]);

    \Thrive_Dash_List_Manager::$should_include = true;

    require_once __DIR__ . '/../../inc/forms/class-bento-thrive-themes-events.php';
});

afterEach(function () {
    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, []);
});

test('Thrive filters register Bento connection callbacks', function () {
    global $__wp_test_state;

    expect($__wp_test_state['actions'])->toHaveKey('tve_filter_available_connection');
    expect($__wp_test_state['actions'])->toHaveKey('tvd_api_available_connections');
});

test('Thrive available connections include Bento entry', function () {
    $connections = add_bento_connection([]);

    expect($connections)->toHaveKey('bento');
    expect($connections['bento'])->toBe('Thrive_Dash_List_Connection_Bento');
});

test('Thrive API inclusion respects connection state', function () {
    $lists = add_bento_connection_to_thrive([], false, ['only_names' => false]);
    expect($lists)->toHaveKey('bento');

    \Thrive_Dash_List_Manager::$should_include = false;
    $lists = add_bento_connection_to_thrive([], false, ['only_names' => false]);
    expect($lists)->toBe([]);
});

test('Thrive connection dispatches Bento event when adding subscriber', function () {
    global $__wp_test_state;

    $__wp_test_state['remote_posts'] = [];

    $connection = new \Thrive_Dash_List_Connection_Bento('bento');
    $connection->add_subscriber('main_list', [
        'email' => 'thrive@example.com',
        'name' => 'Thrive User',
        'form_identifier' => 'form-1',
    ]);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$thrive.optin.form-1');
    expect($event['email'])->toBe('thrive@example.com');
});

test('Thrive connection skips dispatch when email missing', function () {
    global $__wp_test_state;

    $__wp_test_state['remote_posts'] = [];

    $connection = new \Thrive_Dash_List_Connection_Bento('bento');
    $result = $connection->add_subscriber('main_list', [
        'email' => '',
        'name' => 'No Email',
        'form_identifier' => 'form-2',
    ]);

    expect($result)->toBeFalse();
    expect($__wp_test_state['remote_posts'])->toBeEmpty();
});
