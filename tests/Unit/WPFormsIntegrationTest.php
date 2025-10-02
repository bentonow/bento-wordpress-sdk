<?php

namespace Tests\Unit;

use Bento_Events_Controller;

require_once __DIR__ . '/../../inc/forms/class-wp-forms-form-handler.php';

use WPForms_Bento_Integration;

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

test('WPForms integration triggers Bento event with sanitized fields', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $integration = new WPForms_Bento_Integration();

    $fields = [
        1 => [
            'type' => 'email',
            'label' => 'Email Address',
            'value' => 'lead@example.com',
        ],
        2 => [
            'type' => 'text',
            'label' => 'Company',
            'value' => 'ACME Inc.',
        ],
    ];

    $form_data = [
        'id' => 999,
        'settings' => [
            'form_title' => 'WPForms Lead',
            'bento_enable' => '1',
        ],
    ];

    $integration->handle_form_submission($fields, [], $form_data, 1234);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$wpforms.wpforms-lead');
    expect($event['email'])->toBe('lead@example.com');
    expect($event['fields']['company'])->toBe('ACME Inc.');
});

test('WPForms integration skips submission when no email present', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $integration = new WPForms_Bento_Integration();

    $fields = [
        1 => [
            'type' => 'text',
            'label' => 'First Name',
            'value' => 'Taylor',
        ],
    ];

    $form_data = [
        'id' => 1001,
        'settings' => [
            'form_title' => 'No Email Form',
            'bento_enable' => '1',
        ],
    ];

    $integration->handle_form_submission($fields, [], $form_data, 5678);

    expect($__wp_test_state['remote_posts'])->toBeEmpty();
});
