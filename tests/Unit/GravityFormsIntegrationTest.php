<?php

namespace Tests\Unit;

use Bento_GFForms_Form_Handler;
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

test('Gravity Forms handler converts submission to Bento event', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    Bento_GFForms_Form_Handler::init();

    $hook = $__wp_test_state['actions']['gform_after_submission'][0];
    $callback = $hook['callback'];

    $form = [
        'id' => 23,
        'title' => 'Newsletter Signup',
        'fields' => [
            ['id' => '1', 'label' => 'Email', 'type' => 'email'],
            ['id' => '2', 'label' => 'First Name', 'type' => 'text'],
        ],
    ];

    $entry = [
        '1' => 'subscriber@example.com',
        '2' => 'Taylor',
    ];

    $callback($entry, $form);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$GFormsSubmit:Newsletter Signup-23');
    expect($event['email'])->toBe('subscriber@example.com');
    expect($event['fields']['First Name'])->toBe('Taylor');
});

test('Gravity Forms handler falls back to ID when title missing', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    Bento_GFForms_Form_Handler::init();
    $hook = $__wp_test_state['actions']['gform_after_submission'][0];
    $callback = $hook['callback'];

    $form = [
        'id' => 99,
        'title' => '',
        'fields' => [
            ['id' => '1', 'label' => 'Email', 'type' => 'email'],
        ],
    ];

    $entry = [
        '1' => 'untitled@example.com',
    ];

    $callback($entry, $form);

    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$GFormsSubmit:99');
});

test('Gravity Forms handler skips dispatch when email value missing', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    Bento_GFForms_Form_Handler::init();
    $hook = $__wp_test_state['actions']['gform_after_submission'][0];
    $callback = $hook['callback'];

    $form = [
        'id' => 24,
        'title' => 'No Email Form',
        'fields' => [
            ['id' => '1', 'label' => 'Email', 'type' => 'email'],
            ['id' => '2', 'label' => 'Name', 'type' => 'text'],
        ],
    ];

    $entry = [
        '1' => '',
        '2' => 'Taylor',
    ];

    $callback($entry, $form);

    expect($__wp_test_state['remote_posts'])->toBeEmpty();
});
