<?php
defined('ABSPATH') || exit;

class Bento_Settings_Controller {
    private $config;

    public function __construct($config = null) {
        $this->config = $config ?? new WordPress_Configuration();
        add_action('wp_ajax_bento_update_settings', [$this, 'handle_update_settings']);
        add_action('wp_ajax_bento_validate_connection', [$this, 'handle_validate_connection']);
        add_action('wp_ajax_bento_fetch_authors', [$this, 'handle_fetch_authors']);
        add_action('wp_ajax_bento_purge_debug_log', [$this, 'handle_purge_debug_log']);
        add_action('wp_ajax_bento_verify_events_queue', [$this, 'handle_verify_events_queue']);
        add_action('wp_ajax_bento_send_event_notification', [$this, 'handle_send_event_notification']);
        add_action('wp_ajax_bento_get_latest_event', [$this, 'handle_get_latest_event']);
        add_action('wp_ajax_test_bento_event', [$this, 'handle_test_bento_event']);
    }

    public function handle_update_settings(): void {
        check_ajax_referer('bento_settings', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $key = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';

        if ($key === '') {
            wp_send_json_error(['message' => 'Invalid setting key']);
        }

        $raw_value = $_POST['value'] ?? '';
        $value = $this->prepare_setting_value($key, $raw_value);

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

    public function handle_send_event_notification(): void {
        check_ajax_referer('bento_settings', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $installed_integrations = [
            'WooCommerce' => class_exists('WooCommerce'),
            'LearnDash' => defined('LEARNDASH_VERSION'),
            'SureCart' => class_exists('SureCart'),
            'EDD' => class_exists('Easy_Digital_Downloads'),
            'Forms' => class_exists('WPForms') || class_exists('ElementorPro') || class_exists('Bento_Elementor_Form_Handler'),
        ];

        $available = array_keys(array_filter($installed_integrations));
        if (empty($available)) {
            $available = ['Unknown'];
        }

        $integration = $available[array_rand($available)];

        $unique_suffix = uniqid('', true);
        $email = $this->config->get_option('bento_from_email');

        if (empty($email)) {
            $authors_response = $this->config->fetch_authors();
            $author_list = $authors_response['data']['data'] ?? [];
            if (!empty($author_list) && !empty($author_list[0]['attributes']['email'])) {
                $email = $author_list[0]['attributes']['email'];
            }
        }

        if (empty($email)) {
            wp_send_json_error(['message' => 'Please configure a Bento author email before sending a test event.']);
        }

        $event_type = '$test_event.' . strtolower(str_replace(' ', '_', $integration));
        $details = $this->generate_test_event_details($integration, $unique_suffix);
        $custom_fields = [
            'bento_debug_test' => true,
            'integration' => $integration,
            'unique_ref' => $unique_suffix,
        ];

        $result = Bento_Events_Controller::trigger_event(
            get_current_user_id(),
            $event_type,
            $email,
            $details,
            $custom_fields
        );

        if (!$result) {
            wp_send_json_error(['message' => 'Failed to send test event to Bento. Check API credentials.']);
        }

        $event_data = [
            'id' => 'test_' . $unique_suffix,
            'type' => $event_type,
            'integration' => $integration,
            'email' => $email,
            'timestamp' => time(),
            'message' => 'Debug test event successfully dispatched to Bento.',
            'is_error' => false,
        ];

        set_transient('bento_latest_event', $event_data, 60);

        wp_send_json_success(['event' => $event_data]);
    }

    private function generate_test_event_details($integration, $suffix) {
        switch ($integration) {
            case 'WooCommerce':
                return [
                    'order_id' => 'test-order-' . substr($suffix, -6),
                    'value' => [
                        'currency' => 'USD',
                        'amount' => 1999,
                    ],
                ];
            case 'LearnDash':
                return [
                    'course_id' => rand(1000, 9999),
                    'course_name' => 'Sample Course',
                    'progress' => rand(10, 90),
                ];
            case 'SureCart':
                return [
                    'checkout_id' => 'test-checkout-' . substr($suffix, -6),
                    'status' => 'paid',
                    'value' => [
                        'currency' => 'USD',
                        'amount' => 2999,
                    ],
                ];
            case 'EDD':
                return [
                    'download_id' => rand(2000, 9999),
                    'download_name' => 'Sample Download',
                ];
            case 'Forms':
                return [
                    'form_id' => rand(1, 100),
                    'form_title' => 'Sample Form Submission',
                ];
            default:
                return [
                    'note' => 'Generic test event',
                ];
        }
    }

    public function handle_get_latest_event(): void {
        check_ajax_referer('bento_settings', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $event_data = get_transient('bento_latest_event');
        
        if ($event_data) {
            // Delete transient after retrieval to prevent duplicates
            delete_transient('bento_latest_event');
            wp_send_json_success(['event' => $event_data]);
        } else {
            wp_send_json_success(['event' => null]);
        }
    }
    
    public function handle_test_bento_event(): void {
        check_ajax_referer('bento_settings', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        if (!class_exists('Bento_Events_Controller')) {
            wp_send_json_error(['message' => 'Event dispatcher is unavailable.']);
            return;
        }

        $user_id = get_current_user_id();
        $admin_email = get_option('admin_email');

        if (empty($admin_email) || !is_email($admin_email)) {
            wp_send_json_error(['message' => 'Admin email is not configured.']);
            return;
        }

        try {
            // Test event trigger
            $result = Bento_Events_Controller::trigger_event(
                $user_id,
                'test_wpforms_event',
                $admin_email,
                ['test' => 'data'],
                ['custom_field' => 'test_value']
            );
        
            wp_send_json_success([
                'message' => 'Test event triggered',
                'result' => $result,
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Test failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Normalize incoming setting values based on the key that is being updated.
     * Ensures JSON payloads are preserved while other values remain sanitized.
     */
    private function prepare_setting_value(string $key, $raw_value) {
        if ($key === 'bento_connection_status') {
            $value = is_string($raw_value) ? wp_unslash($raw_value) : '';
            $decoded_status = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_status)) {
                wp_send_json_error(['message' => 'Invalid connection status payload']);
            }

            return $decoded_status;
        }

        if (is_array($raw_value)) {
            $unslashed = wp_unslash($raw_value);

            foreach ($unslashed as $index => $item) {
                $unslashed[$index] = is_scalar($item) ? sanitize_text_field((string) $item) : '';
            }

            return $unslashed;
        }

        $value = wp_unslash($raw_value);

        if (!is_scalar($value)) {
            return '';
        }

        return sanitize_text_field((string) $value);
    }
}
