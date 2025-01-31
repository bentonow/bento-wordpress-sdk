<?php
defined('ABSPATH') || exit;

class Bento_Mail_Logger {
    private $log_file;
    private $max_size = 10485760; // 10MB
    private $hash_expiry = 300; // 5 minutes in seconds

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/bento/mail-logs.json';
        wp_mkdir_p(dirname($this->log_file));
    }

    public function log_mail($data) {
        if (!$this->is_logging_enabled()) {
            return;
        }

        $data['timestamp'] = time();

        // Ensure we have an ID for tracking
        if (empty($data['id'])) {
            $data['id'] = uniqid('mail_', true);
        }

        $this->write_log($data);
        $this->check_size();
    }

    public function is_duplicate($hash) {
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

    private function write_log($data) {
        $logs = $this->read_logs();
        array_unshift($logs, $data);

        $this->ensure_directory_exists();
        if (file_put_contents($this->log_file, wp_json_encode($logs)) === false) {
            Bento_Logger::error('[Mail Logger] Failed to write to log file: ' . $this->log_file);
        }
    }

    private function ensure_directory_exists() {
        $dir = dirname($this->log_file);
        if (!file_exists($dir)) {
            if (!wp_mkdir_p($dir)) {
                Bento_Logger::error('[Mail Logger] Failed to create directory: ' . $dir);
            }
        }
    }

    private function check_size() {
        if (!file_exists($this->log_file)) {
            return;
        }

        if (filesize($this->log_file) > $this->max_size) {
            $logs = $this->read_logs();
            $logs = array_slice($logs, 0, 1000);
            file_put_contents($this->log_file, wp_json_encode($logs));
        }
    }

    public function clear_logs() {
        $this->ensure_directory_exists();
        file_put_contents($this->log_file, wp_json_encode([]));
    }

    public function read_logs($limit = null) {
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

    private function is_logging_enabled() {
        $options = get_option('bento_settings');
        return !empty($options['bento_enable_mail_logging']);
    }
}