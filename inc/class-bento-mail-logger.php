<?php
defined('ABSPATH') || exit;

class Bento_Mail_Logger implements Mail_Logger_Interface {
    private $log_file;
    private $max_size = 10485760; // 10MB
    private $hash_expiry = 300; // 5 minutes in seconds

    public function __construct(?string $log_file = null) {
        if ($log_file) {
            $this->log_file = $log_file;
        } else {
            $upload_dir = wp_upload_dir();
            $this->log_file = $upload_dir['basedir'] . '/bento/mail-logs.json';
        }
        wp_mkdir_p(dirname($this->log_file));
    }

    public function log_mail(array $data): void {
        if (!$this->is_logging_enabled()) {
            return;
        }

        $data['timestamp'] = time();

        if (empty($data['id'])) {
            $data['id'] = uniqid('mail_', true);
        }

        $this->write_log($data);
        $this->check_size();
    }

    public function is_duplicate(string $hash): bool {
        $logs = $this->read_logs();
        $cutoff = time() - $this->hash_expiry;

        foreach ($logs as $log) {
            if (!empty($log['hash']) &&
                $log['hash'] === $hash &&
                !empty($log['timestamp']) &&
                $log['timestamp'] > $cutoff) {
                return true;
            }
        }
        return false;
    }

    public function clear_logs(): void {
        $this->ensure_directory_exists();
        file_put_contents($this->log_file, wp_json_encode([]));
    }

    public function read_logs(?int $limit = null): array {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $content = file_get_contents($this->log_file);
        if (empty($content)) {
            return [];
        }

        $logs = json_decode($content, true);
        if (!is_array($logs)) {
            return [];
        }

        if ($limit && count($logs) > $limit) {
            $logs = array_slice($logs, 0, $limit);
        }

        return $logs;
    }

    private function write_log(array $data): void {
        $logs = $this->read_logs();
        array_unshift($logs, $data);

        $this->ensure_directory_exists();
        if (file_put_contents($this->log_file, wp_json_encode($logs)) === false) {
            Bento_Logger::error('Failed to write to log file: ' . $this->log_file);
        }
    }

    private function ensure_directory_exists(): void {
        $dir = dirname($this->log_file);
        if (!file_exists($dir)) {
            if (!wp_mkdir_p($dir)) {
                Bento_Logger::error('Failed to create directory: ' . $dir);
            }
        }
    }

    private function check_size(): void {
        if (!file_exists($this->log_file)) {
            return;
        }

        if (filesize($this->log_file) > $this->max_size) {
            $logs = $this->read_logs();
            $logs = array_slice($logs, 0, 1000);
            file_put_contents($this->log_file, wp_json_encode($logs));
        }
    }

    private function is_logging_enabled(): bool {
        $options = get_option('bento_settings');
        return !empty($options['bento_enable_mail_logging']);
    }

    public function get_log_file_path(): string {
        return $this->log_file;
    }
}