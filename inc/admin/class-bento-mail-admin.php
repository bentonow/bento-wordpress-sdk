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
        if (!current_user_can('manage_options')) {
            return;
        }

        if ($hook !== 'bento_page_bento-mail-logs') {
            return;
        }

        $manifest_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/build/manifest.json';
        if (!file_exists($manifest_path)) {
            return;
        }

        $manifest = json_decode(file_get_contents($manifest_path), true);
        if (!isset($manifest['assets/js/src/bento-app.jsx'])) {
            return;
        }

        // Get the app bundle
        $app_file = $manifest['assets/js/src/bento-app.jsx']['file'];
        $css_files = $manifest['assets/js/src/bento-app.jsx']['css'] ?? [];

        wp_enqueue_script('wp-element');
        wp_enqueue_script(
            'bento-admin-app',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/build/' . $app_file,
            ['wp-element'],
            null,
            true
        );

        foreach ($css_files as $css_file) {
            wp_enqueue_style(
                'bento-admin-' . basename($css_file, '.css'),
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/build/' . $css_file,
                [],
                null
            );
        }

        $admin_data = [
            'mailLogs' => $this->logger->read_logs(1000),
            'nonce' => wp_create_nonce('bento_settings'),
            'adminUrl' => admin_url('admin-post.php')
        ];

        wp_localize_script('bento-admin-app', 'bentoAdmin', $admin_data);
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
        echo '<div id="bento-mail-logs"></div>';
    }

    public function clear_logs() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('bento_settings');

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