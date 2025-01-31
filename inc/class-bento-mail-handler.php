<?php
defined('ABSPATH') || exit;

class Bento_Mail_Handler {
    private static $instance = null;
    private $options;
    private $mail_logger;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        $this->options = get_option('bento_settings');
        Bento_Logger::log('[Mail Handler] Initializing with options: ' . print_r($this->options, true));

        if (empty($this->options['bento_enable_transactional']) || $this->options['bento_enable_transactional'] !== '1') {
            Bento_Logger::log('[Mail Handler] Transactional emails not enabled, skipping initialization');
            return;
        }

        require_once dirname(__FILE__) . '/class-bento-mail-logger.php';
        $this->mail_logger = new Bento_Mail_Logger();
        add_filter('pre_wp_mail', [$this, 'handle_wp_mail'], 10, 2);
        Bento_Logger::log('[Mail Handler] Added pre_wp_mail filter');
    }

    public function handle_wp_mail($null, $atts) {
        Bento_Logger::log('[Mail Handler] Handle mail called');
        $to = is_array($atts['to']) ? $atts['to'] : explode(',', $atts['to']);
        $to = array_map('trim', $to);
        $subject = $atts['subject'] ?? '';
        $message = $atts['message'] ?? '';
        $headers = $atts['headers'] ?? '';
        $attachments = $atts['attachments'] ?? array();

        // Generate unique ID for this email
        $mail_id = uniqid('mail_', true);

        $this->mail_logger->log_mail([
            'id' => $mail_id,
            'type' => 'mail_received',
            'to' => implode(',', $to),
            'subject' => $subject,
            'success' => true
        ]);

        if (!empty($attachments)) {
            $this->mail_logger->log_mail([
                'id' => $mail_id,
                'type' => 'wordpress_fallback',
                'reason' => 'attachments',
                'to' => implode(',', $to),
                'subject' => $subject
            ]);
            return null;
        }

        // Check for duplicates
        $hash = md5($to[0] . $subject . $message);
        if ($this->mail_logger->is_duplicate($hash)) {
            $this->mail_logger->log_mail([
                'id' => $mail_id,
                'type' => 'blocked_duplicate',
                'to' => implode(',', $to),
                'subject' => $subject,
                'hash' => $hash
            ]);
            return true;
        }

        $headers = $this->parse_headers($headers);
        $result = $this->send_via_bento([
            'id' => $mail_id,
            'to' => $to[0],
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
            'hash' => $hash
        ]);

        if (!$result) {
            $this->mail_logger->log_mail([
                'id' => $mail_id,
                'type' => 'bento_failed',
                'to' => implode(',', $to),
                'subject' => $subject,
                'hash' => $hash,
                'success' => false
            ]);
            return null;
        }

        $this->mail_logger->log_mail([
            'id' => $mail_id,
            'type' => 'bento_sent',
            'to' => implode(',', $to),
            'subject' => $subject,
            'hash' => $hash,
            'success' => true
        ]);

        return true;
    }

    private function parse_headers($headers) {
        if (empty($headers)) {
            return array();
        }

        if (!is_array($headers)) {
            $headers = explode("\n", str_replace("\r\n", "\n", $headers));
        }

        $parsed = array();
        foreach ($headers as $header) {
            if (strpos($header, ':') === false) {
                continue;
            }
            list($name, $value) = explode(':', $header, 2);
            $parsed[trim($name)] = trim($value);
        }

        return $parsed;
    }

    private function send_via_bento($data) {
        $bento_site_key = $this->options['bento_site_key'] ?? '';
        $bento_publishable_key = $this->options['bento_publishable_key'] ?? '';
        $bento_secret_key = $this->options['bento_secret_key'] ?? '';
        $from_email = $this->options['bento_from_email'] ?? get_option('admin_email');

        if (empty($bento_site_key) || empty($bento_publishable_key) || empty($bento_secret_key)) {
            Bento_Logger::log('[Mail Handler] Missing API credentials');
            return false;
        }

        $auth = base64_encode($bento_publishable_key . ':' . $bento_secret_key);
        $api_url = 'https://app.bentonow.com/api/v1/batch/emails?site_uuid=' . $bento_site_key;

        $body = array(
            'emails' => array(
                array(
                    'to' => $data['to'],
                    'from' => $from_email,
                    'subject' => $data['subject'],
                    'html_body' => $data['message'],
                    'transactional' => true
                )
            )
        );

        Bento_Logger::log('[Mail Handler] Sending request to Bento API');

        $response = wp_remote_post(
            $api_url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $auth,
                    'User-Agent' => 'bento-wordpress-' . $bento_site_key
                ),
                'body' => wp_json_encode($body),
                'timeout' => 30,
            )
        );

        if (is_wp_error($response)) {
            Bento_Logger::log('[Mail Handler] API Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        Bento_Logger::log('[Mail Handler] API Response Code: ' . $response_code);
        Bento_Logger::log('[Mail Handler] API Response Body: ' . $response_body);

        return $response_code === 200;
    }
}