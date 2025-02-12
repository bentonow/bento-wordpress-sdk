<?php

defined('ABSPATH') || exit;

class Bento_Mail_Handler implements Mail_Handler_Interface {
    private static $instance = null;
    private $config;
    private $logger;
    private $http_client;
    private $mail_logger;

    public function __construct(
        ?Configuration_Interface $config = null,
        ?Logger_Interface $logger = null,
        ?Http_Client_Interface $http_client = null,
        ?Mail_Logger_Interface $mail_logger = null
    ) {
        $this->config = $config ?? new WordPress_Configuration();
        $this->logger = $logger ?? new WordPress_Logger();
        $this->http_client = $http_client ?? new WordPress_Http_Client();
        $this->mail_logger = $mail_logger ?? new Bento_Mail_Logger();
    }

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        if (!$this->is_enabled()) {
            $this->logger->log('Transactional emails not enabled, skipping initialization');
            return;
        }

        add_filter('pre_wp_mail', [$this, 'handle_wp_mail'], 10, 2);
        $this->logger->log('Added pre_wp_mail filter');
    }

    public function handle_wp_mail($null, array $atts): ?bool {
        $this->logger->log('Handle mail called');

        $to = is_array($atts['to']) ? $atts['to'] : explode(',', $atts['to']);
        $to = array_map('trim', $to);
        $headers = is_array($atts['headers']) ? $atts['headers'] : explode("\n", str_replace("\r\n", "\n", $atts['headers']));

        return $this->handle_mail(
            $to[0],
            $atts['subject'] ?? '',
            $atts['message'] ?? '',
            $headers,
            $atts['attachments'] ?? []
        );
    }

    public function handle_mail(string $to, string $subject, string $message, array $headers = [], array $attachments = []): bool {
        $mail_id = uniqid('mail_', true);

        $this->mail_logger->log_mail([
            'id' => $mail_id,
            'type' => 'mail_received',
            'to' => $to,
            'subject' => $subject,
            'success' => true
        ]);

        if (!empty($attachments)) {
            $this->mail_logger->log_mail([
                'id' => $mail_id,
                'type' => 'wordpress_fallback',
                'reason' => 'attachments',
                'to' => $to,
                'subject' => $subject
            ]);
            return false;
        }

        $hash = md5($to . $subject . $message);
        if ($this->mail_logger->is_duplicate($hash)) {
            $this->mail_logger->log_mail([
                'id' => $mail_id,
                'type' => 'blocked_duplicate',
                'to' => $to,
                'subject' => $subject,
                'hash' => $hash
            ]);
            return true;
        }

        $result = $this->send_via_bento([
            'id' => $mail_id,
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $this->parse_headers($headers),
            'hash' => $hash
        ]);

        $this->mail_logger->log_mail([
            'id' => $mail_id,
            'type' => $result ? 'bento_sent' : 'bento_failed',
            'to' => $to,
            'subject' => $subject,
            'hash' => $hash,
            'success' => $result
        ]);

        return $result;
    }

    private function parse_headers(array $headers): array {
        $parsed = [];
        foreach ($headers as $header) {
            if (strpos($header, ':') === false) {
                continue;
            }
            list($name, $value) = explode(':', $header, 2);
            $parsed[trim($name)] = trim($value);
        }
        return $parsed;
    }

    private function send_via_bento(array $data): bool {
        $site_key = $this->config->get_option('bento_site_key');
        $publishable_key = $this->config->get_option('bento_publishable_key');
        $secret_key = $this->config->get_option('bento_secret_key');
        $from_email = $this->config->get_option('bento_from_email', $this->config->get_option('admin_email'));

        if (empty($site_key) || empty($publishable_key) || empty($secret_key)) {
            $this->logger->error('Missing API credentials');
            return false;
        }

        $auth = base64_encode($publishable_key . ':' . $secret_key);
        $url = "https://app.bentonow.com/api/v1/batch/emails?site_uuid={$site_key}";

        $body = [
            'emails' => [
                [
                    'to' => $data['to'],
                    'from' => $from_email,
                    'subject' => $data['subject'],
                    'html_body' => $data['message'],
                    'transactional' => true
                ]
            ]
        ];

        try {
            $response = $this->http_client->post($url, $body, [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $auth,
                'User-Agent' => 'bento-wordpress-' . $site_key
            ]);

            return $response['status_code'] === 200;
        } catch (\Exception $e) {
            $this->logger->error('API Error: ' . $e->getMessage());
            return false;
        }
    }

    private function is_enabled(): bool {
        return $this->config->get_option('bento_enable_transactional') === '1';
    }
}