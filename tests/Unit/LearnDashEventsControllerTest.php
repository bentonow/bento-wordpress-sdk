<?php

namespace Tests\Unit;

if (!defined('LEARNDASH_VERSION')) {
    define('LEARNDASH_VERSION', 'tests');
}

use LearnDash_Bento_Events;

beforeEach(function () {
    wp_test_reset_state();
});

test('LearnDash not completed guard respects repeat interval', function () {
    global $__wp_test_state;

    $reflection = new \ReflectionClass(LearnDash_Bento_Events::class);
    $method = $reflection->getMethod('should_send_not_completed_event');
    $method->setAccessible(true);

    // No history -> should send event.
    $__wp_test_state['user_meta'] = [];
    expect($method->invoke(null, 12, 7))->toBeTrue();

    // Recently sent -> should skip when repeat interval defined.
    $__wp_test_state['user_meta'][12][LearnDash_Bento_Events::BENTO_LAST_NOT_COMPLETED_EVENT_SENT_META_KEY] = time();
    expect($method->invoke(null, 12, 7))->toBeFalse();

    // Past the interval -> should send again.
    $__wp_test_state['user_meta'][12][LearnDash_Bento_Events::BENTO_LAST_NOT_COMPLETED_EVENT_SENT_META_KEY] = strtotime('-8 day');
    expect($method->invoke(null, 12, 7))->toBeTrue();

    // Interval not configured -> only first send allowed.
    $__wp_test_state['user_meta'][12][LearnDash_Bento_Events::BENTO_LAST_NOT_COMPLETED_EVENT_SENT_META_KEY] = time();
    expect($method->invoke(null, 12, 0))->toBeFalse();
});
