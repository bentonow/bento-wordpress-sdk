<?php
/**
 * Bento Mail Admin
 *
 * @package BentoHelper
 */

defined('ABSPATH') || exit;

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
        if ('bento_page_bento-mail-logs' !== $hook) {
            return;
        }

        wp_enqueue_style('bento-mail-admin', plugins_url('assets/css/mail-admin.css', dirname(dirname(__FILE__))));
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

        include dirname(__FILE__) . '/views/mail-logs.php';
    }

    public function clear_logs() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('clear_bento_mail_logs');

        $this->logger->clear_logs();

        wp_safe_redirect(add_query_arg(
            ['page' => 'bento-mail-logs', 'cleared' => '1'],
            admin_url('admin.php')
        ));
        exit;
    }

    public function toggle_logging() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('toggle_bento_mail_logging');

        $options = get_option('bento_settings');
        $options['bento_enable_mail_logging'] = !empty($_POST['enable_logging']);
        update_option('bento_settings', $options);

        wp_safe_redirect(add_query_arg(
            ['page' => 'bento-mail-logs', 'toggled' => '1'],
            admin_url('admin.php')
        ));
        exit;
    }
}