<?php
class Bento_Mail_Admin {
    private $logger;

    public function __construct() {
        require_once dirname(dirname(__FILE__)) . '/class-bento-mail-logger.php';
        $this->logger = new Bento_Mail_Logger();
    }

    public function init() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_post_clear_bento_mail_logs', [$this, 'clear_logs']);
        add_action('admin_post_toggle_bento_mail_logging', [$this, 'toggle_logging']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts($hook) {
        Bento_logger::log('Bento: Script enqueue function called with hook: ' . $hook);

        if ('bento_page_bento-mail-logs' !== $hook) {
            Bento_logger::log('Bento: Hook did not match, skipping script load');
            return;
        }

        $manifest_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/build/manifest.json';
        Bento_logger::log('Bento: Looking for manifest at: ' . $manifest_path);

        if (!file_exists($manifest_path)) {
            Bento_logger::log('Bento: Manifest file not found at ' . $manifest_path);
            return;
        }

        $manifest = json_decode(file_get_contents($manifest_path), true);
        Bento_logger::log('Bento: Manifest contents: ' . print_r($manifest, true));

        $entry_key = 'assets/js/src/mail-logs.jsx';  // Match the exact key from manifest

        if (!isset($manifest[$entry_key])) {
            Bento_logger::log('Bento: Mail logs entry not found in manifest for key: ' . $entry_key);
            return;
        }

        // Get the JS file
        $js_file = $manifest[$entry_key]['file'];
        $js_path = plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/build/' . $js_file;

        // Get the CSS file
        if (isset($manifest[$entry_key]['css']) && is_array($manifest[$entry_key]['css'])) {
            foreach ($manifest[$entry_key]['css'] as $css_file) {
                wp_enqueue_style(
                    'bento-mail-logs-' . basename($css_file, '.css'),
                    plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/build/' . $css_file,
                    [],
                    null
                );
            }
        }

        // Enqueue React
        wp_enqueue_script('wp-element');

        // Enqueue our script
        wp_enqueue_script('bento-mail-logs', $js_path, ['wp-element'], null, true);

        Bento_logger::log('Bento: Scripts and styles enqueued successfully');
    }

    public function add_menu_page() {
        add_submenu_page(
            'bento-setting-admin',
            __('Mail Logs', 'bentonow'),
            __('Mail Logs', 'bentonow'),
            'manage_options',
            'bento-mail-logs',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = get_option('bento_settings');
        $logs = $this->logger->read_logs(1000);

        // Format timestamps
        foreach ($logs as &$log) {
            $log['timestamp'] = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                $log['timestamp']
            );
        }

        include dirname(__FILE__) . '/views/mail-logs.php';
    }

    public function clear_logs() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('clear_bento_mail_logs');

        $this->logger->clear_logs();

        if (wp_doing_ajax()) {
            wp_send_json_success(['message' => __('Logs cleared successfully.', 'bentonow')]);
        } else {
            wp_safe_redirect(add_query_arg(
                ['page' => 'bento-mail-logs', 'cleared' => '1'],
                admin_url('admin.php')
            ));
            exit;
        }
    }

    public function toggle_logging() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('toggle_bento_mail_logging');

        $options = get_option('bento_settings', array());
        $options['bento_enable_mail_logging'] = !empty($_POST['enable_logging']);
        update_option('bento_settings', $options);

        wp_safe_redirect(add_query_arg(
            ['page' => 'bento-mail-logs', 'toggled' => '1'],
            admin_url('admin.php')
        ));
        exit;
    }
}