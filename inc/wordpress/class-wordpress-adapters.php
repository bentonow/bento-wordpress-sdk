<?php
defined('ABSPATH') || exit;

class WordPress_Configuration implements Configuration_Interface {
    private $options;

    public function __construct() {
        $this->options = get_option('bento_settings', []);
    }

    public function get_option(string $key, $default = null) {
        return $this->options[$key] ?? get_option($key, $default);
    }

    public function update_option(string $key, $value): bool {
        Bento_Logger::log("Updating option $key to $value");
        $this->options[$key] = $value;
        return update_option('bento_settings', $this->options);
    }

    public function get_credentials(): array {
        return [
            'site_key' => $this->get_option('bento_site_key', ''),
            'publishable_key' => $this->get_option('bento_publishable_key', ''),
            'secret_key' => $this->get_option('bento_secret_key', '')
        ];
    }

    public function validate_credentials(array $credentials): array {
        if (empty($credentials['site_key']) || empty($credentials['publishable_key']) || empty($credentials['secret_key'])) {
            Bento_Logger::log('Validation attempted with missing credentials');
            return $this->update_connection_status(401);
        }

        $auth = base64_encode($credentials['publishable_key'] . ':' . $credentials['secret_key']);

        Bento_Logger::log('Validating Bento credentials');

        $response = wp_remote_get(
            'https://app.bentonow.com/api/v1/fetch/authors?site_uuid=' . urlencode($credentials['site_key']),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $auth,
                    'User-Agent' => 'bento-wordpress-' . $credentials['site_key']
                ],
                'timeout' => 15
            ]
        );

        if (is_wp_error($response)) {
            Bento_Logger::error('Bento credential validation failed: ' . $response->get_error_message());
            return $this->update_connection_status(500);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        Bento_Logger::log("Bento credential validation response - Status: $status_code");
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return $this->update_connection_status($status_code, $data);
    }

    public function fetch_authors(): array {
        $credentials = $this->get_credentials();
        return $this->validate_credentials($credentials);
    }

    private function update_connection_status(int $status_code, array $data = []): array {
        $status = [
            'connected' => $status_code >= 200 && $status_code < 300,
            'message' => $this->get_status_message($status_code),
            'code' => $status_code,
            'timestamp' => time(),
        ];

        $this->update_option('bento_connection_status', $status);

        return [
            'success' => $status['connected'],
            'status_code' => $status_code,
            'connection_status' => $status,
            'data' => $data
        ];
    }

    private function get_status_message(int $status_code): string {
        return match (true) {
            $status_code >= 200 && $status_code < 300 => 'Connected',
            $status_code === 401 => 'Invalid credentials',
            $status_code === 403 => 'Access denied',
            $status_code >= 400 && $status_code < 500 => 'Invalid request',
            default => 'Service error'
        };
    }
}

class WordPress_Logger implements Logger_Interface {
    public function log(string $message, string $level = 'info'): void {
        Bento_Logger::log($message, $level);
    }

    public function error(string $message): void {
        Bento_Logger::error($message);
    }
}

class WordPress_Http_Client implements Http_Client_Interface {
    public function post(string $url, array $data, array $headers = []): array {
        $args = [
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        return [
            'body' => wp_remote_retrieve_body($response),
            'status_code' => wp_remote_retrieve_response_code($response),
            'headers' => wp_remote_retrieve_headers($response)
        ];
    }
}