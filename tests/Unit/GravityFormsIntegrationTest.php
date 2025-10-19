<?php

namespace Tests\Unit;

use Bento_GFForms_Form_Handler;
use Bento_Events_Controller;

function fetch_gform_after_submission_callback(): array
{
    global $__wp_test_state;
    $actions = $__wp_test_state['actions'] ?? null;

    if (!is_array($actions)) {
        expect()->fail('Expected $__wp_test_state["actions"] to be an array before asserting hooks.');
    }

    if (
        !array_key_exists('gform_after_submission', $actions) ||
        !is_array($actions['gform_after_submission']) ||
        count($actions['gform_after_submission']) === 0
    ) {
        $available = implode(', ', array_keys($actions));
        expect()->fail(sprintf(
            'Gravity Forms handler did not register the gform_after_submission hook. Available hooks: [%s]',
            $available
        ));
    }

    $hook = $actions['gform_after_submission'][0];

    if (!isset($hook['callback']) || !is_callable($hook['callback'])) {
        expect()->fail('Registered gform_after_submission hook is missing a callable callback.');
    }

    return [$hook, $hook['callback']];
}

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

    [, $callback] = fetch_gform_after_submission_callback();

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

    $remote_posts = $__wp_test_state['remote_posts'] ?? null;

    if (!is_array($remote_posts) || count($remote_posts) === 0) {
        expect()->fail('Gravity Forms handler did not dispatch any remote posts for a valid submission.');
    }

    $payload = json_decode($remote_posts[0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$GFormsSubmit:Newsletter Signup-23');
    expect($event['email'])->toBe('subscriber@example.com');
    expect($event['fields']['First Name'])->toBe('Taylor');
});

test('Gravity Forms handler falls back to ID when title missing', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    Bento_GFForms_Form_Handler::init();
    [, $callback] = fetch_gform_after_submission_callback();

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

    $remote_posts = $__wp_test_state['remote_posts'] ?? null;

    if (!is_array($remote_posts) || count($remote_posts) === 0) {
        expect()->fail('Gravity Forms handler failed to dispatch a remote post when form title was empty.');
    }

    $payload = json_decode($remote_posts[0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$GFormsSubmit:99');
});

test('Gravity Forms handler skips dispatch when email value missing', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    Bento_GFForms_Form_Handler::init();
    [, $callback] = fetch_gform_after_submission_callback();

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
