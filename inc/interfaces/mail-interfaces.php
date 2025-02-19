<?php
defined('ABSPATH') || exit;

interface Mail_Handler_Interface {
    public function handle_mail($to, $subject, $message, $headers = [], $attachments = []): bool;
}

interface Configuration_Interface {
    public function get_option($key, $default = null);
    public function update_option($key, $value): bool;
    public function validate_credentials($credentials): array;
}

interface Logger_Interface {
    public function log($message, $level = 'info'): void;
    public function error($message): void;
}

interface Http_Client_Interface {
    public function post($url, $data, $headers = []): array;
}

interface Mail_Logger_Interface {
    public function log_mail($data): void;
    public function is_duplicate($hash): bool;
    public function clear_logs(): void;
    public function read_logs($limit = null): array;
}