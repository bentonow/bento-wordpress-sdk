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