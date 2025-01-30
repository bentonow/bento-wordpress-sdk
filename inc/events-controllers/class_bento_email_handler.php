<?php
class Bento_Email_Handler extends Bento_Events_Controller {
    /**
     * Constructor.
     */
    public function __construct() {
        // Register the custom cron interval first
        add_filter('cron_schedules', array($this, 'add_cron_interval'));

        // Only hook into email processing if the feature is enabled
        $options = get_option('bento_settings');
        if (!empty($options['bento_enable_transactional']) && $options['bento_enable_transactional'] === '1') {
            Bento_Logger::log('Bento Email Handler: Initialized and enabled');


            // Hook into WordPress email sending
            add_filter('wp_mail', array($this, 'intercept_wp_mail'), 1, 1);

            // Add cron job to process email queue
            add_action('bento_process_email_queue', array($this, 'process_email_queue'));

            if (!wp_next_scheduled('bento_process_email_queue')) {
                wp_schedule_event(time(), 'every_ten_seconds', 'bento_process_email_queue');
                Bento_Logger::log('Bento Email Handler: Scheduled cron job');
            }
        } else {
                Bento_Logger::log('Bento Email Handler: Not enabled in settings');
            // Clean up cron job if feature is disabled
            $this->remove_cron_job();
        }
    }

    /**
     * Add custom cron interval
     */
    public function add_cron_interval($schedules) {
        $schedules['every_ten_seconds'] = array(
            'interval' => 10, // 10 seconds
            'display'  => __('Every 10 Seconds', 'bentonow')
        );
        return $schedules;
    }

    /**
     * Remove the cron job
     */
    public function remove_cron_job() {
        $timestamp = wp_next_scheduled('bento_process_email_queue');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'bento_process_email_queue');
        }
    }

    /**
     * Clean up when plugin is deactivated
     */
    public static function deactivate() {
        $timestamp = wp_next_scheduled('bento_process_email_queue');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'bento_process_email_queue');
        }
    }

    /**
     * Intercept WordPress emails and queue them for Bento
     */
    public function intercept_wp_mail($args) {
        Bento_Logger::log('Bento Email Handler: Intercepting email');
        Bento_Logger::log('Email Details: ' . print_r($args, true));


        // Check if email has attachments
        if (!empty($args['attachments'])) {
            Bento_Logger::log('Bento Email Handler: Email has attachments, falling back to WordPress mail');
            return $args; // Return original args to let WordPress handle emails with attachments
        }

        // Queue the email for Bento
        $this->queue_email([
            'to' => $args['to'],
            'subject' => $args['subject'],
            'message' => $args['message'],
            'headers' => $args['headers'] ?? array(),
        ]);

        // Return original args to allow normal WordPress email processing
        return $args;
    }

    /**
     * Queue an email for sending via Bento
     */
    private function queue_email($email_data) {
        Bento_Logger::log('Bento Email Handler: Queueing email');

        // Get current queue
        $queue = get_option('bento_email_queue', array());

        // Create a unique hash for this email
        $email_hash = $this->create_email_hash($email_data);

        Bento_Logger::log('Email Hash: ' . $email_hash);

        // Check for duplicates in the queue using stored hashes
        foreach ($queue as $item) {
            // If we find a match with stored hash, log it and return without adding to queue
            if ($email_hash === $item['hash']) {
                Bento_Logger::log('Bento Email Handler: Duplicate email detected and discarded');
                return;
            }
        }

        // If we get here, this is not a duplicate, so add it to the queue
        $queue[] = array(
            'email_data' => $email_data,
            'timestamp' => time(),
            'hash' => $email_hash
        );

        $updated = update_option('bento_email_queue', $queue);
            Bento_Logger::log('Bento Email Handler: Queue updated: ' . ($updated ? 'true' : 'false'));
            Bento_Logger::log('Current Queue Size: ' . count($queue));
    }

    /**
     * Process queued emails
     */
    public function process_email_queue() {
        Bento_Logger::log('Bento Email Handler: Processing queue');

        $queue = get_option('bento_email_queue', array());
        Bento_Logger::log('Queue size: ' . count($queue));

        $new_queue = array();

        foreach ($queue as $item) {
            Bento_Logger::log('Processing email: ' . print_r($item['email_data'], true));

            $success = $this->send_via_bento($item['email_data']);
            Bento_Logger::log('Send result: ' . ($success ? 'success' : 'failed'));


            if (!$success) {
                // Only keep items that are less than 24 hours old
                if ($item['timestamp'] > (time() - 86400)) {
                    $new_queue[] = $item; // Keep hash in queue for failed items
                }
            }
        }

        update_option('bento_email_queue', $new_queue);
        Bento_Logger::log('Bento Email Handler: Queue processing complete. Remaining items: ' . count($new_queue));

    }

    /**
     * Create a unique hash for an email based on its normalized content
     */
    private function create_email_hash($email_data) {
        // Convert recipient(s) to a consistent format
        $to = is_array($email_data['to']) ? implode(',', $email_data['to']) : $email_data['to'];

        // Combine key elements of the email
        $hash_input = $to . '|' .
            $email_data['subject'];

        // Create a hash that's unlikely to have collisions
        return md5($hash_input);
    }

    /**
     * Send email via Bento API
     */
    private function send_via_bento($email_data) {
        // Get Bento credentials and settings
        $options = get_option('bento_settings');
        $bento_site_key = $options['bento_site_key'] ?? '';
        $bento_publishable_key = $options['bento_publishable_key'] ?? '';
        $bento_secret_key = $options['bento_secret_key'] ?? '';
        $from_email = $options['bento_from_email'] ?? get_option('admin_email');
        $transactional_override = !empty($options['bento_transactional_override']) && $options['bento_transactional_override'] === '1';


        Bento_Logger::log('Bento Email Handler: Sending via API');
        Bento_Logger::log('Using from email: ' . $from_email);


        if (empty($bento_site_key) || empty($bento_publishable_key) || empty($bento_secret_key)) {
            Bento_Logger::log('Bento Email Handler: Missing credentials');
            return false;
        }

        // Create authorization header
        $auth = base64_encode($bento_publishable_key . ':' . $bento_secret_key);

        // Format the request
        $api_url = 'https://app.bentonow.com/api/v1/batch/emails?site_uuid=' . $bento_site_key;

        $body = array(
            'emails' => array(
                array(
                    'to' => $email_data['to'],
                    'from' => $from_email,
                    'subject' => $email_data['subject'],
                    'html_body' => $email_data['message'],
                    'transactional' => $transactional_override
                )
            )
        );

        Bento_Logger::log('Request body: ' . print_r($body, true));

        // Get plugin version
        $plugin_version = bento_helper()->version;


        $response = wp_remote_post(
            $api_url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $auth,
                    'User-Agent' => 'bento-wordpress-'.$bento_site_key,
                    'X-Bento-WP-Plug-Version' => $plugin_version
                ),
                'body' => wp_json_encode($body),
                'timeout' => 30,
            )
        );

        if (is_wp_error($response)) {
            Bento_Logger::log('Bento Email Handler Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        Bento_Logger::log('API Response Code: ' . $response_code);
        Bento_Logger::log('API Response Body: ' . $response_body);

        if ($response_code !== 200) {

            Bento_Logger::log('Bento Email Error: ' . $response_code . ' - ' . $response_body);

            return false;
        }

        return true;
    }
}