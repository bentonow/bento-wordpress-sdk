<?php
/**
 * Plugin Name: Bento Helper
 * Plugin URI: https://github.com/bentonow/bento-wordpress-sdk
 * Description: Email marketing, live chat, and analytics for WooCommerce stores.
 * Version: 1.0.1
 * Author: Bento
 * Author URI: https://bentonow.com
 * Text Domain: bentonow
 * WC requires at least: 2.6.0
 * WC tested up to: 4.2.0.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class Bento_Helper {

	/**
	 * Current version of Bento.
	 *
	 * @var string
	 */
	public $version = '1.0.1';

	/**
	 * URL dir for plugin.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Main Bento Helper Instance.
	 *
	 * Ensures only one instance of the Bento Helper is loaded or can be loaded.
	 *
	 * @return Bento Helper - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Set URL.
		$this->url = plugin_dir_url( __FILE__ );
	}

	/**
	 * Start plugin.
	 */
	public function init() {
		// Activate notice (shown once).
		add_action( 'admin_notices', array( $this, 'activate_notice' ) );

		if ( class_exists( 'WooCommerce' ) ) {
			require_once 'inc/ajax.php';
			require_once 'inc/orders.php';
		}

		require_once 'inc/class-bentosettingspage.php';
		require_once 'inc/custom.php';

		// load events controllers.
		require_once 'inc/class-bento-events-controller.php';
		Bento_Events_Controller::init();

		// Plugin textdomain.
		load_plugin_textdomain(
			'bentonow',
			false,
			basename( dirname( __FILE__ ) ) . '/languages/'
		);
	}

	/**
	 * Run on activation.
	 */
	public static function activate() {
		// Set Bento's show activation notice option to true if it isn't already false (only first time).
		if ( get_option( 'bento_show_activation_notice', true ) ) {
			update_option( 'bento_show_activation_notice', true );
		}
	}

	/**
	 * Run on deactivation.
	 */
	public static function deactivate() {
		// remove cron jobs.
		Bento_Events_Controller::unschedule_bento_events_cron();
		WP_Bento_Events::unschedule_bento_wp_cron();
		LearnDash_Bento_Events::unschedule_bento_wp_cron();
	}

	/**
	 * Activate notice (if we should).
	 */
	public function activate_notice() {
		if ( get_option( 'bento_show_activation_notice', false ) ) {
			echo '<div class="notice notice-success"><p>' .
			sprintf( __( 'The Bento WordPress SDK is active!' ) ) .
			'</p></div>';

			// Disable notice option.
			update_option( 'bento_show_activation_notice', false );
		}
	}
}

// Notice after it's been activated.
register_activation_hook( __FILE__, array( 'Bento_Helper', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Bento_Helper', 'deactivate' ) );

/**
 * For plugin-wide access to initial instance.
 */
function bento_helper() {
	return Bento_Helper::instance();
}

bento_helper();
