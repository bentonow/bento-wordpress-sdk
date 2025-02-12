<?php

define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../vendor/autoload.php';

// Define required WordPress constants
defined('WP_CONTENT_DIR') || define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

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
    function get_option() {
        return [];
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}