<?php

namespace Tests\Unit;

use Bento_Bricks_Form_Handler;
use Bento_Events_Controller;

beforeEach(function () {
    wp_test_reset_state();

    // Ensure Bento options contain credentials to allow send_event processing.
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
    // Reset cached Bento options between tests.
    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, []);
});

test('Bricks handler skips dispatch when email is missing', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $form = new class {
        public function get_fields() {
            return [
                'formId' => 10,
                'postId' => 55,
                'form-field-name' => 'Name',
            ];
        }

        public function get_settings() {
            return [];
        }
    };

    Bento_Bricks_Form_Handler::handle_form_submission($form);

    expect($__wp_test_state['remote_posts'])->toBeEmpty();
});

test('Bricks handler dispatches event with sanitized payload', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $form = new class {
        public function get_fields() {
            return [
                'formId' => 21,
                'postId' => 34,
                'email' => 'person@example.com',
                'event' => '$CustomEvent',
                'bento_custom_field' => 'value',
                'form-field-random' => 'should-strip',
            ];
        }

        public function get_settings() {
            return [];
        }
    };

    Bento_Bricks_Form_Handler::handle_form_submission($form);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);

    $body = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $body['events'][0];

    expect($event['type'])->toBe('$CustomEvent');
    expect($event['email'])->toBe('person@example.com');
    expect($event['fields'])
        ->toMatchArray([
            'bricks_last_form_id' => 21,
            'bricks_last_post_id' => 34,
            'custom_field' => 'value',
        ]);
    expect($event['details']['formId'])->toBe(21);
    expect($event['details']['postId'])->toBe(34);
});
