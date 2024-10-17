<?php
/**
 * Plugin Name: Bento Helper
 * Plugin URI: https://github.com/bentonow/bento-wordpress-sdk
 * Description: Email marketing, live chat, and analytics for WooCommerce stores.
 * Version: 2.0.1
 * Author: Bento
 * Author URI: https://bentonow.com
 * Text Domain: bentonow
 * WC requires at least: 2.6.0
 * WC tested up to: 8.3.1
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class Bento_Helper {

	/**
	 * Current version of Bento.
	 *
	 * @var string
	 */
	public $version = '2.0.1';

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
		add_action( 'before_woocommerce_init', array( $this, 'woocommerce_declare_hpos_support' ) );

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
		}

		require_once 'inc/class-bentosettingspage.php';
		require_once 'inc/custom.php';
		require_once 'inc/class-bento-events-controller.php';
		require_once 'inc/forms/class-bento-bricks-form-handler.php';
		require_once 'inc/forms/class-bento-elementor-form-handler.php';

		// Here we load up all the automated event handlers.
		Bento_Events_Controller::init();

		// Here we load up all the different form handlers.
		Bento_Bricks_Form_Handler::init(); # 
		Bento_Elementor_Form_Handler::init();

		if ( class_exists( 'WPForms' ) ) {
			require_once 'inc/forms/class-wp-forms-form-handler.php';
		}

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
		if ( class_exists( 'Bento_Events_Controller' ) ) {
			Bento_Events_Controller::remove_cron_jobs();
		}
		if ( class_exists( 'WP_Bento_Events' ) ) {
			WP_Bento_Events::remove_cron_jobs();
		}
		if ( class_exists( 'LearnDash_Bento_Events' ) ) {
			LearnDash_Bento_Events::remove_cron_jobs();
		}
	}

	/**
	 * Activation notice for onboarding at first activation or if site key is not set.
	 */
	public function activate_notice() {

		$settings = get_option( 'bento_settings' );
		$site_key = !empty( $settings['bento_site_key'] ) ? $settings['bento_site_key'] : false;

		if ( get_option( 'bento_show_activation_notice', false ) || ! $site_key) {
			echo '<div class="notice notice-success">
			<p>' . sprintf( __( 'Welcome to Bento! To get started, please <a href="%s">configure your settings</a> and connect your Bento account.', 'bentonow' ), 
			esc_url( admin_url( 'admin.php?page=bento-setting-admin' ) ) ) . '</p>
		  </div>';
			// Disable notice option.
			update_option( 'bento_show_activation_notice', false );
		}
	}

	/**
	 * Confirm WooCommerce support for HPOS.
	 *
	 * @return void
	 */
	public function woocommerce_declare_hpos_support() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
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


