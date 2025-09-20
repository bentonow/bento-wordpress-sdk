<?php

define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../vendor/autoload.php';

// Define required WordPress constants
defined('WP_CONTENT_DIR') || define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

global $__wp_test_state;
$__wp_test_state = [
    'options' => [],
    'transients' => [],
    'deleted_options' => [],
    'deleted_transients' => [],
    'remote_posts' => [],
    'user_meta' => [],
    'users' => [
        'id' => [],
        'email' => [],
        'login' => [],
    ],
    'actions' => [],
];

if (!function_exists('wp_test_reset_state')) {
    function wp_test_reset_state() {
        global $__wp_test_state;
        $__wp_test_state['deleted_options'] = [];
        $__wp_test_state['deleted_transients'] = [];
        $__wp_test_state['remote_posts'] = [];
    }
}

// Stub WordPress functions used in classes
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return ['basedir' => sys_get_temp_dir()];
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p() {
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $__wp_test_state;
        return $__wp_test_state['options'][$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $__wp_test_state;
        $__wp_test_state['options'][$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        global $__wp_test_state;
        $__wp_test_state['deleted_options'][] = $option;
        unset($__wp_test_state['options'][$option]);
        return true;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        global $__wp_test_state;
        $__wp_test_state['transients'][$transient] = $value;
        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        global $__wp_test_state;
        return $__wp_test_state['transients'][$transient] ?? false;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        global $__wp_test_state;
        $__wp_test_state['deleted_transients'][] = $transient;
        unset($__wp_test_state['transients'][$transient]);
        return true;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('current_time')) {
    function current_time($format) {
        return date($format);
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = []) {
        global $__wp_test_state;
        $__wp_test_state['remote_posts'][] = ['url' => $url, 'args' => $args];

        return [
            'body' => json_encode(['success' => true]),
            'response' => ['code' => 200],
        ];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'] ?? '';
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return $response['response']['code'] ?? 0;
    }
}

if (!function_exists('wp_remote_retrieve_headers')) {
    function wp_remote_retrieve_headers($response) {
        return $response['headers'] ?? [];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        global $__wp_test_state;
        $__wp_test_state['actions'][$hook][] = compact('callback', 'priority', 'accepted_args');
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return add_action($hook, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        global $__wp_test_state;
        if (empty($__wp_test_state['actions'][$hook])) {
            return;
        }

        foreach ($__wp_test_state['actions'][$hook] as $registered) {
            $callback = $registered['callback'];
            $accepted_args = $registered['accepted_args'];
            $callback(...array_slice($args, 0, $accepted_args));
        }
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook) {
        return false;
    }
}

if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event($timestamp, $hook) {
        return true;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook) {
        return true;
    }
}

if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 0) {
        return mt_rand($min, $max);
    }
}

if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs(intval($maybeint));
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($path) {
        return 'http://example.com/plugins/' . basename(dirname($path)) . '/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($path) {
        return dirname($path) . '/';
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        // store for reference if needed later
        global $__wp_test_state;
        $__wp_test_state['activation_hook'] = $callback;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        global $__wp_test_state;
        $__wp_test_state['deactivation_hook'] = $callback;
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain() {
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script() {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style() {
        return true;
    }
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script() {
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return md5($action);
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single = true) {
        global $__wp_test_state;
        return $__wp_test_state['user_meta'][$user_id][$key] ?? '';
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $key, $value) {
        global $__wp_test_state;
        $__wp_test_state['user_meta'][$user_id][$key] = $value;
        return true;
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        global $__wp_test_state;
        return $__wp_test_state['users']['id'][$user_id] ?? null;
    }
}

if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) {
        global $__wp_test_state;

        if ('id' === $field) {
            return $__wp_test_state['users']['id'][$value] ?? null;
        }

        if ('email' === $field) {
            return $__wp_test_state['users']['email'][$value] ?? null;
        }

        if ('login' === $field) {
            return $__wp_test_state['users']['login'][$value] ?? null;
        }

        return null;
    }
}

if (!function_exists('get_users')) {
    function get_users($args = []) {
        global $__wp_test_state;
        return $__wp_test_state['users']['list'] ?? [];
    }
}
