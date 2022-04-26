<?php
/**
 * Bento settings page
 *
 * @package BentoHelper
 */

/**
 * Bento settings page class
 */
class BentoSettingsPage {

	/**
	 * Holds the values to be used in the fields callbacks
	 *
	 * @var arrray
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		$this->options = get_option( 'bento_settings' );

		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings".
		add_menu_page(
			'Bento',
			'Bento',
			'manage_options',
			'bento-setting-admin',
			array( $this, 'create_admin_page' ),
			plugin_dir_url( __DIR__ ) . 'assets/img/bento-logo-colour.png'
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bento Settings', 'bentonow' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields.
				settings_fields( 'bento_option_group' );
				do_settings_sections( 'bento-setting-admin' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
			'bento_option_group', // Option group.
			'bento_settings', // Option name.
			array( $this, 'sanitize' ) // Sanitize.
		);

		add_settings_section(
			'bento_setting_section_id', // ID.
			esc_html__( 'Configure Bento Site Key', 'bentonow' ), // Title.
			function() {
				echo '<p>' . esc_html__( 'You can find your site key in your account dashboard. If you have trouble finding it, just ask support.', 'bentonow' ) . '</p>';
			}, // Callback.
			'bento-setting-admin' // Page.
		);

		add_settings_field(
			'bento_site_key', // ID.
			esc_html__( 'Site Key', 'bentonow' ), // Title.
			array( $this, 'bento_setting_field_callback' ), // Callback.
			'bento-setting-admin', // Page.
			'bento_setting_section_id', // Section.
			array(
				'id'    => 'bento_site_key',
				'value' => $this->options['bento_site_key'] ?? '',
				'type'  => 'text',
			)
		);

		add_settings_section(
			'bento_setting_section_events_sender', // ID.
			esc_html__( 'Configure Bento Events Sender', 'bentonow' ), // Title.
			function() {
				echo '<p>' . esc_html__( 'Configure how to send events for Bento. Use zero to disable an option.', 'bentonow' ) . '</p>';
			}, // Callback.
			'bento-setting-admin' // Page.
		);

		add_settings_field(
			'bento_events_recurrence', // ID.
			esc_html__( 'Send events for Bento each (minutes)', 'bentonow' ), // Title.
			array( $this, 'bento_setting_field_callback' ), // Callback.
			'bento-setting-admin', // Page.
			'bento_setting_section_events_sender', // Section.
			array(
				'id'         => 'bento_events_recurrence',
				'value'      => $this->options['bento_events_recurrence'] ?? 1,
				'type'       => 'number',
				'attributes' => array(
					'min' => 0,
					'max' => 60,
				),
			)
		);

		add_settings_field(
			'bento_events_user_not_logged', // ID.
			esc_html__( 'Send "user not logged" events after (days)', 'bentonow' ), // Title.
			array( $this, 'bento_setting_field_callback' ), // Callback.
			'bento-setting-admin', // Page.
			'bento_setting_section_events_sender', // Section.
			array(
				'id'         => 'bento_events_user_not_logged',
				'value'      => $this->options['bento_events_user_not_logged'] ?? 5,
				'type'       => 'number',
				'attributes' => array(
					'min' => 0,
					'max' => 360,
				),
			)
		);

		add_settings_field(
			'bento_events_user_not_complete_content', // ID.
			esc_html__( 'Send "user not complete content" events after (days)', 'bentonow' ), // Title.
			array( $this, 'bento_setting_field_callback' ), // Callback.
			'bento-setting-admin', // Page.
			'bento_setting_section_events_sender', // Section.
			array(
				'id'         => 'bento_events_user_not_complete_content',
				'value'      => $this->options['bento_events_user_not_complete_content'] ?? 5,
				'type'       => 'number',
				'attributes' => array(
					'min' => 0,
					'max' => 360,
				),
			)
		);

		add_settings_field(
			'bento_events_repeat_not_event', // ID.
			esc_html__( 'Repeat user "not-events" each (days)', 'bentonow' ), // Title.
			array( $this, 'bento_setting_field_callback' ), // Callback.
			'bento-setting-admin', // Page.
			'bento_setting_section_events_sender', // Section.
			array(
				'id'         => 'bento_events_repeat_not_event',
				'value'      => $this->options['bento_events_repeat_not_event'] ?? 2,
				'type'       => 'number',
				'attributes' => array(
					'min' => 0,
					'max' => 360,
				),
			)
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys.
	 */
	public function sanitize( $input ) {
		$new_input = array();

		if ( isset( $input['bento_site_key'] ) ) {
			$new_input['bento_site_key'] = sanitize_text_field( $input['bento_site_key'] );
		}
		if ( isset( $input['bento_events_recurrence'] ) ) {
			$new_input['bento_events_recurrence'] = absint( sanitize_text_field( $input['bento_events_recurrence'] ) );
		}

		if ( isset( $input['bento_events_user_not_logged'] ) ) {
			$new_input['bento_events_user_not_logged'] = absint( sanitize_text_field( $input['bento_events_user_not_logged'] ) );
		}

		if ( isset( $input['bento_events_user_not_complete_content'] ) ) {
			$new_input['bento_events_user_not_complete_content'] = absint( sanitize_text_field( $input['bento_events_user_not_complete_content'] ) );
		}

		if ( isset( $input['bento_events_repeat_not_event'] ) ) {
			$new_input['bento_events_repeat_not_event'] = absint( sanitize_text_field( $input['bento_events_repeat_not_event'] ) );
		}

		return $new_input;
	}

	/**
	 * Get the settings option and print the corresponding element
	 *
	 * @param array $args Field arguments.
	 */
	public function bento_setting_field_callback( $args ) {
		$attributes      = $args['attributes'] ?? array();
		$attributes_html = '';
		foreach ( $attributes as $key => $value ) {
			$attributes_html .= sprintf( '%s="%s" ', esc_attr( $key ), esc_attr( $value ) );
		}

		printf(
			'<input type="%s" %s id="%s" name="bento_settings[%s]" value="%s" />',
			esc_attr( $args['type'] ),
			$attributes_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes are escaped above.
			esc_attr( $args['id'] ),
			esc_attr( $args['id'] ),
			esc_attr( $args['value'] )
		);
	}
}

if ( is_admin() ) {
	$bento_settings_page = new BentoSettingsPage();
}
