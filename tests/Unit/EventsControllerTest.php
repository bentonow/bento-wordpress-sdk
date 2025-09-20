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
});

afterEach(function () {
    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, []);
});

test('enqueue_event immediately sends payload', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $method = $reflection->getMethod('enqueue_event');
    $method->setAccessible(true);

    $method->invoke(null, 123, '$OrderPlaced', 'buyer@example.com', ['order_id' => 456]);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);

    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$OrderPlaced');
    expect($event['email'])->toBe('buyer@example.com');
    expect($event['details']['order_id'])->toBe(456);
});

test('bento_send_events_hook clears legacy queue artifacts', function () {
    global $__wp_test_state;
    $__wp_test_state['options'][Bento_Events_Controller::EVENTS_QUEUE_OPTION_KEY] = [['foo' => 'bar']];
    $__wp_test_state['transients'][Bento_Events_Controller::EVENTS_QUEUE_OPTION_KEY] = [['baz' => 'qux']];
    $__wp_test_state['transients'][Bento_Events_Controller::EVENTS_QUEUE_OPTION_KEY . '_temp_keys'] = ['key-one'];

    Bento_Events_Controller::bento_send_events_hook();

    expect($__wp_test_state['deleted_options'])
        ->toContain(Bento_Events_Controller::EVENTS_QUEUE_OPTION_KEY);
    expect($__wp_test_state['deleted_transients'])
        ->toContain(Bento_Events_Controller::EVENTS_QUEUE_OPTION_KEY);
    expect($__wp_test_state['deleted_transients'])
        ->toContain(Bento_Events_Controller::EVENTS_QUEUE_OPTION_KEY . '_temp_keys');
});
