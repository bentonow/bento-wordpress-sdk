<?php
defined('ABSPATH') || exit;

interface Mail_Handler_Interface {
    public function handle_mail(string $to, string $subject, string $message, array $headers = [], array $attachments = []): bool;
}

interface Configuration_Interface {
    public function get_option(string $key, $default = null);
}

interface Logger_Interface {
    public function log(string $message, string $level = 'info'): void;
    public function error(string $message): void;
}

interface Http_Client_Interface {
    public function post(string $url, array $data, array $headers = []): array;
}

interface Mail_Logger_Interface {
    public function log_mail(array $data): void;
    public function is_duplicate(string $hash): bool;
    public function clear_logs(): void;
    public function read_logs(?int $limit = null): array;
}