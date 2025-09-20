<?php

namespace Tests\Unit;

use Bento_Events_Controller;
use WPForms_Bento;
use Mockery as m;

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
    m::close();
});

test('WPForms Bento provider sends event with mapped fields', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $provider = new WPForms_Bento();
    $provider->init();

    $fields = [
        '1' => ['value' => 'lead@example.com'],
        '2' => ['value' => 'Casey Customer'],
        '3' => ['value' => 'ACME Inc.'],
    ];

    $form_data = [
        'id' => 999,
        'settings' => [
            'form_title' => 'WPForms Lead',
        ],
        'providers' => [
            'bento' => [
                [
                    'name' => 'Primary Connection',
                    'list_id' => 'list-1',
                    'account_id' => 'acct-1',
                    'fields' => [
                        'email' => '1.value',
                        'full_name' => '2.value',
                        'company' => '3.value',
                    ],
                ],
            ],
        ],
    ];

    $provider->process_entry($fields, [], $form_data, 1234);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('WPForms Lead');
    expect($event['email'])->toBe('lead@example.com');
    expect($event['fields']['first_name'])->toBe('Casey');
    expect($event['fields']['company'])->toBe('ACME Inc.');
});

test('WPForms Bento provider honours conditional logic', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $provider = m::mock(WPForms_Bento::class)->makePartial();
    $provider->shouldAllowMockingProtectedMethods();
    $provider->shouldReceive('process_conditionals')->andReturn(false);
    $provider->init();

    $fields = [
        '1' => ['value' => 'noreply@example.com'],
    ];

    $form_data = [
        'id' => 1001,
        'settings' => [
            'form_title' => 'Conditional Form',
        ],
        'providers' => [
            'bento' => [
                [
                    'name' => 'Conditional Connection',
                    'list_id' => 'list-2',
                    'account_id' => 'acct-2',
                    'fields' => [
                        'email' => '1.value',
                    ],
                ],
            ],
        ],
    ];

    $provider->process_entry($fields, [], $form_data, 5678);

    expect($__wp_test_state['remote_posts'])->toBeEmpty();
});
