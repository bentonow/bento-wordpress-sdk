<?php
defined('ABSPATH') || exit;

class Bento_Settings_Controller {
    private $config;

    public function __construct(Configuration_Interface $config = null) {
        $this->config = $config ?? new WordPress_Configuration();
        add_action('wp_ajax_bento_update_settings', [$this, 'handle_update_settings']);
        add_action('wp_ajax_bento_validate_connection', [$this, 'handle_validate_connection']);
        add_action('wp_ajax_bento_fetch_authors', [$this, 'handle_fetch_authors']);
        add_action('wp_ajax_bento_purge_debug_log', [$this, 'handle_purge_debug_log']);
        add_action('wp_ajax_bento_verify_events_queue', [$this, 'handle_verify_events_queue']);
    }

    public function handle_update_settings(): void {
        check_ajax_referer('bento_settings', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $key = sanitize_text_field($_POST['key']);
        $value = sanitize_text_field($_POST['value']);

        $result = $this->config->update_option($key, $value);
        wp_send_json(['success' => $result]);
    }

    public function handle_validate_connection(): void {
        check_ajax_referer('bento_settings', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $this->config->update_option('bento_site_key', sanitize_text_field($_POST['site_key']));
        $this->config->update_option('bento_publishable_key', sanitize_text_field($_POST['publishable_key']));
        $this->config->update_option('bento_secret_key', sanitize_text_field($_POST['secret_key']));

        $result = $this->config->fetch_authors();
        wp_send_json($result);
    }

    public function handle_fetch_authors(): void {
        check_ajax_referer('bento_settings', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $result = $this->config->fetch_authors();
        wp_send_json($result);
    }

    /**
     * Handle debug log purge request
     * Clears the WordPress debug.log file to reclaim disk space
     */
    public function handle_purge_debug_log(): void {
        check_ajax_referer('bento_settings', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        try {
            $debug_log_path = WP_CONTENT_DIR . '/debug.log';
            
            // Check if file exists before attempting to clear it
            if (!file_exists($debug_log_path)) {
                wp_send_json_success(['message' => 'Debug log successfully cleared']);
                return;
            }

            // Check if file is writable
            if (!is_writable($debug_log_path)) {
                wp_send_json_error(['message' => 'Failed to clear debug log. Please check file permissions']);
                return;
            }

            // Clear the debug log by writing empty content
            $result = file_put_contents($debug_log_path, '');
            
            if ($result === false) {
                wp_send_json_error(['message' => 'Failed to clear debug log. Please check file permissions']);
                return;
            }

            wp_send_json_success(['message' => 'Debug log successfully cleared']);
            
        } catch (Exception $e) {
            error_log('Bento: Failed to purge debug log - ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to clear debug log. Please check file permissions']);
        }
    }

    /**
     * Handle event queue verification request
     * Removes the bento_events_queue option from the database
     */
    public function handle_verify_events_queue(): void {
        check_ajax_referer('bento_settings', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        try {
            // Remove the bento_events_queue option from the database
            $result = delete_option('bento_events_queue');
            
            // delete_option returns true if the option was deleted, false if it didn't exist
            // Both cases are considered successful for this operation
            wp_send_json_success(['message' => 'Event queue successfully cleaned']);
            
        } catch (Exception $e) {
            error_log('Bento: Failed to verify events queue - ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to clean event queue. Database operation failed']);
        }
    }
}