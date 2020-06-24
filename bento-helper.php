<?php
/**
 * Plugin Name: Bento Helper
 * Plugin URI: https://bentonow.com
 * Description: TODO
 * Version: 0.1.0
 * Author: Bento
 * Author URI: https://bentonow.com
 * Text Domain: bentonow
 * WC requires at least: 2.6.0
 * WC tested up to: 4.2.0.
 */
class Bento_Helper
{
  /**
   * Current version of Bento.
   */
  public $version = '0.1.0';

  /**
   * URL dir for plugin.
   */
  public $url;

  /**
   * The single instance of the class.
   */
  protected static $_instance = null;

  /**
   * Main Bento Helper Instance.
   *
   * Ensures only one instance of the Bento Helper is loaded or can be loaded.
   *
   * @return Bento Helper - Main instance.
   */
  public static function instance()
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  /**
   * Constructor.
   */
  public function __construct()
  {
    add_action('plugins_loaded', [$this, 'init']);

    // Set URL
    $this->url = plugin_dir_url(__FILE__);
  }

  /**
   * Start plugin.
   */
  public function init()
  {
    // Activate notice (shown once)
    add_action('admin_notices', [$this, 'activate_notice']);

    if (class_exists('WooCommerce')) {
      require_once 'inc/ajax.php';
      require_once 'inc/orders.php';
    }

    require_once 'inc/admin.php';
    require_once 'inc/custom.php';

    // Plugin textdomain
    load_plugin_textdomain(
      'bento',
      false,
      basename(dirname(__FILE__)) . '/languages/'
    );
  }

  /**
   * Run on activation.
   */
  public static function activate()
  {
    // Set Bento's show activation notice option to true if it isn't already false (only first time)
    if (get_option('bento_show_activation_notice', true)) {
      update_option('bento_show_activation_notice', true);
    }
  }

  /**
   * Activate notice (if we should).
   */
  public function activate_notice()
  {
    if (get_option('bento_show_activation_notice', false)) {
      echo '<div class="notice notice-success"><p>' .
        sprintf(__('The Bento Wordpress SDK is active!')) .
        '</p></div>';

      // Disable notice option
      update_option('bento_show_activation_notice', false);
    }
  }
}

// Notice after it's been activated
register_activation_hook(__FILE__, ['Bento_Helper', 'activate']);

/**
 * For plugin-wide access to initial instance.
 */
function Bento_Helper()
{
  return Bento_Helper::instance();
}

Bento_Helper();
