<?php
defined('ABSPATH') || exit;

class Bento_Settings_Controller {
    private $config;

    public function __construct(Configuration_Interface $config = null) {
        $this->config = $config ?? new WordPress_Configuration();
        add_action('wp_ajax_bento_update_settings', [$this, 'handle_update_settings']);
        add_action('wp_ajax_bento_validate_connection', [$this, 'handle_validate_connection']);
        add_action('wp_ajax_bento_fetch_authors', [$this, 'handle_fetch_authors']);
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
}