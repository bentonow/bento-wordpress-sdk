<?php

beforeEach(function () {
    wp_test_reset_state();
});

test('plugin bootstrap returns helper instance', function () {
    wp_test_reset_state();

    if (!function_exists('bento_helper')) {
        require_once __DIR__ . '/../../bento-helper.php';
    } else {
        bento_helper();
    }

    $helper = bento_helper();

    expect($helper)->toBeInstanceOf(Bento_Helper::class);
    expect(class_exists('Bento_Helper'))->toBeTrue();
});
