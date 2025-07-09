<?php
class BentoSettingsPage {
    private $config;
    private $settings_controller;
    private $menu_icon = 'dashicons-email';
    private $plugin_url;

    public function __construct() {
        $this->config = new WordPress_Configuration();
        $this->settings_controller = new Bento_Settings_Controller($this->config);
        $this->plugin_url = plugin_dir_url(dirname(__FILE__));
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_plugin_page() {
        add_menu_page(
            'Bento',
            'Bento',
            'manage_options',
            'bento-setting-admin',
            array($this, 'render_settings_page'),
            $this->menu_icon
        );
    }

    public function enqueue_scripts($hook) {
        if (!current_user_can('manage_options')) {
            return;
        }

        if ($hook !== 'toplevel_page_bento-setting-admin' && $hook !== 'bento_page_bento-mail-logs') {
            return;
        }

        $manifest_path = plugin_dir_path(dirname(__FILE__)) . 'assets/build/manifest.json';
        if (!file_exists($manifest_path)) {
            return;
        }

        $manifest = json_decode(file_get_contents($manifest_path), true);
        if (!isset($manifest['assets/js/src/bento-app.jsx'])) {
            return;
        }

        $app_file = $manifest['assets/js/src/bento-app.jsx']['file'];
        $css_files = $manifest['assets/js/src/bento-app.jsx']['css'] ?? [];

        wp_enqueue_script('wp-element');
        wp_enqueue_script(
            'bento-admin-app',
            $this->plugin_url . 'assets/build/' . $app_file,
            ['wp-element'],
            null,
            true
        );

        foreach ($css_files as $css_file) {
            wp_enqueue_style(
                'bento-admin-' . basename($css_file, '.css'),
                $this->plugin_url . 'assets/build/' . $css_file,
                [],
                null
            );
        }

        $plugin_data = $this->get_plugin_data();
        $settings = get_option('bento_settings', []);
        

        $admin_data = [
            'settings' => $settings,
            'plugins' => array_combine(
                array_keys($plugin_data),
                array_map(function($data) { return $data['installed']; }, $plugin_data)
            ),
            'versions' => array_combine(
                array_keys($plugin_data),
                array_map(function($data) { return $data['version']; }, $plugin_data)
            ),
            'nonce' => wp_create_nonce('bento_settings'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url('admin-post.php'),
            'pluginUrl' => $this->plugin_url
        ];

        if ($hook === 'bento_page_bento-mail-logs') {
            require_once dirname(dirname(__FILE__)) . '/inc/class-bento-mail-logger.php';
            $logger = new Bento_Mail_Logger();
            $admin_data['mailLogs'] = $logger->read_logs(1000);
        }

        wp_add_inline_script(
            'bento-admin-app',
            'window.bentoAdmin = ' . wp_json_encode($admin_data) . ';',
            'before'
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div id="bento-settings" class="wrap"></div>';
    }

    private function get_plugin_data() {
        return [
            'WooCommerce' => [
                'installed' => function_exists('WC') && class_exists('WooCommerce'),
                'version' => get_option('woocommerce_version')
            ],
            'LEARNDASH_VERSION' => [
                'installed' => defined('LEARNDASH_VERSION'),
                'version' => defined('LEARNDASH_VERSION') ? LEARNDASH_VERSION : null
            ],
            'SureCart' => [
                'installed' => class_exists('SureCart'),
                'version' => defined('SURECART_VERSION') ? SURECART_VERSION : null
            ],
            'WPForms' => [
                'installed' => class_exists('WPForms'),
                'version' => defined('WPFORMS_VERSION') ? WPFORMS_VERSION : null
            ],
            'Easy_Digital_Downloads' => [
                'installed' => class_exists('Easy_Digital_Downloads'),
                'version' => defined('EDD_VERSION') ? EDD_VERSION : null
            ],
            'ELEMENTOR_VERSION' => [
                'installed' => defined('ELEMENTOR_VERSION'),
                'version' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null
            ],
            'BRICKS_VERSION' => [
                'installed' => defined('BRICKS_VERSION'),
                'version' => defined('BRICKS_VERSION') ? BRICKS_VERSION : null
            ],
            'TVE_IN_ARCHITECT' => [
                'installed' => defined('TVE_IN_ARCHITECT'),
                'version' => defined('TVE_VERSION') ? TVE_VERSION : null
            ],
            'GForms' => [
                'installed' => class_exists('GFCommon'),
                'version' => class_exists('GFCommon') ? GFCommon::$version : null
            ],
        ];
    }
}

if (is_admin()) {
    new BentoSettingsPage();
}