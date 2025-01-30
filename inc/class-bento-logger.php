<?php
/**
 * Bento Logger Class
 *
 * @package BentoHelper
 */

defined('ABSPATH') || exit;

class Bento_Logger {
    /**
     * Debug log file path
     *
     * @var string
     */
    private static $log_file;

    /**
     * Initialize logger
     */
    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/debug.log';
    }

    /**
     * Log a message to debug.log
     *
     * @param string $message The message to log
     * @param string $level Log level (info, error, warning)
     * @return void
     */
    public static function log($message, $level = 'info') {
        $options = get_option('bento_settings');

        if (empty($options['bento_enable_logging']) || $options['bento_enable_logging'] !== '1') {
            return;
        }

        if (!self::$log_file) {
            self::init();
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_message = sprintf(
            "[%s] [Bento] [%s] %s\n",
            $timestamp,
            strtoupper($level),
            $message
        );

        error_log($formatted_message, 3, self::$log_file);
    }

    /**
     * Log an error message
     *
     * @param string $message The error message
     */
    public static function error($message) {
        self::log($message, 'error');
    }

    /**
     * Log a warning message
     *
     * @param string $message The warning message
     */
    public static function warning($message) {
        self::log($message, 'warning');
    }

    /**
     * Log an info message
     *
     * @param string $message The info message
     */
    public static function info($message) {
        self::log($message, 'info');
    }
}