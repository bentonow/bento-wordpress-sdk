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

    require_once __DIR__ . '/../../inc/forms/class-bento-elementor-form-handler.php';
    require_once __DIR__ . '/../../form-actions/bento.php';
});

afterEach(function () {
    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, []);
});

test('Elementor handler skips registration when Pro is absent', function () {
    global $__wp_test_state;

    \Bento_Elementor_Form_Handler::init();
    expect($__wp_test_state['actions'])->not->toHaveKey('elementor_pro/forms/actions/register');
});

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
test('Elementor handler registers action when Pro is available', function () {
    global $__wp_test_state;
    if (!defined('ELEMENTOR_PRO_VERSION')) {
        define('ELEMENTOR_PRO_VERSION', '3.17.0');
    }

    \Bento_Elementor_Form_Handler::init();

    expect($__wp_test_state['actions'])->toHaveKey('elementor_pro/forms/actions/register');
});

test('Elementor form action registers Bento submit handler', function () {
    $registrar = new \ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar();
    \Bento_Elementor_Form_Handler::register_form_action($registrar);

    expect($registrar->registered)->toHaveCount(1);
    expect($registrar->registered[0])->toBeInstanceOf(\Bento_Action_After_Submit::class);
});

test('Elementor Bento action dispatches event on form submission', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $record = new \ElementorPro\Modules\Forms\Classes\Form_Record([
        'form_settings' => [
            'bento_event' => '$elementor.submit',
            'bento_email_field' => 'email',
        ],
        'fields' => [
            'email' => ['value' => 'elementor@example.com'],
            'name' => ['value' => 'Elly Mentor'],
        ],
    ]);

    $original_post = $_POST;
    $_POST['referrer'] = 'http://example.com/from-elementor';

    try {
        $action = new \Bento_Action_After_Submit();
        $action->run($record, new \ElementorPro\Modules\Forms\Classes\Ajax_Handler());
    } finally {
        $_POST = $original_post;
    }

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$elementor.submit');
    expect($event['email'])->toBe('elementor@example.com');
    expect($event['details']['email'])->toBe('elementor@example.com');
});

test('Elementor Bento action aborts when email field missing', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $record = new \ElementorPro\Modules\Forms\Classes\Form_Record([
        'form_settings' => [
            'bento_event' => '$elementor.submit',
            'bento_email_field' => 'email',
        ],
        'fields' => [
            'email' => ['value' => ''],
        ],
    ]);

    $action = new \Bento_Action_After_Submit();
    $action->run($record, new \ElementorPro\Modules\Forms\Classes\Ajax_Handler());

    expect($__wp_test_state['remote_posts'])->toBeEmpty();
});
