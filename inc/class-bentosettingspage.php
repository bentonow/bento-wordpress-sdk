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
			esc_html__( 'Bento API Keys', 'bentonow' ), // Title.
			function() {
				echo '<p>' . esc_html__( 'You can find your API keys at ', 'bentonow' ) . '<a href="https://app.bentonow.com/account/teams" target="_blank">' . esc_html__( 'https://app.bentonow.com/account/teams', 'bentonow' ) . '</a>. ' . esc_html__( 'If you have trouble finding them, please contact our support.', 'bentonow' ) . '</p>';
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

		// Add publishable key field
		add_settings_field(
			'bento_publishable_key',
			esc_html__( 'Publishable Key', 'bentonow' ),
			array( $this, 'bento_setting_field_callback' ),
			'bento-setting-admin',
			'bento_setting_section_id',
			array(
				'id'    => 'bento_publishable_key',
				'value' => $this->options['bento_publishable_key'] ?? '',
				'type'  => 'text',
			)
		);

		// Add secret key field
		add_settings_field(
			'bento_secret_key',
			esc_html__( 'Secret Key', 'bentonow' ),
			array( $this, 'bento_setting_field_callback' ),
			'bento-setting-admin',
			'bento_setting_section_id',
			array(
				'id'    => 'bento_secret_key',
				'value' => $this->options['bento_secret_key'] ?? '',
				'type'  => 'password',
			)
		);

		// Add this new settings field after the existing API key fields
		add_settings_field(
			'bento_enable_tracking', // ID
			esc_html__( 'Enable Site Tracking', 'bentonow' ), // Title
			array( $this, 'bento_setting_field_callback' ), // Callback
			'bento-setting-admin', // Page
			'bento_setting_section_id', // Section
			array(
				'id'    => 'bento_enable_tracking',
				'value' => $this->options['bento_enable_tracking'] ?? '0',
				'type'  => 'checkbox',
				'label' => esc_html__( 'Enable Bento site tracking', 'bentonow' ),
			)
		);

		// show cron settings only if LearnDash is active.
		if ( defined( 'LEARNDASH_VERSION' ) ) {

			add_settings_section(
				'bento_setting_section_events_sender', // ID.
				esc_html__( "Configure Bento's LearnDash Background Events Sender", 'bentonow' ), // Title.
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
					'value'      => $this->options['bento_events_recurrence'] ?? 30,
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
					'value'      => $this->options['bento_events_user_not_logged'] ?? 0,
					'type'       => 'number',
					'attributes' => array(
						'min' => 0,
						'max' => 360,
					),
				)
			);

			add_settings_field(
				'bento_events_user_not_completed_content', // ID.
				esc_html__( 'Send "user not completed content" events after (days)', 'bentonow' ), // Title.
				array( $this, 'bento_setting_field_callback' ), // Callback.
				'bento-setting-admin', // Page.
				'bento_setting_section_events_sender', // Section.
				array(
					'id'         => 'bento_events_user_not_completed_content',
					'value'      => $this->options['bento_events_user_not_completed_content'] ?? 0,
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
					'value'      => $this->options['bento_events_repeat_not_event'] ?? 0,
					'type'       => 'number',
					'attributes' => array(
						'min' => 0,
						'max' => 360,
					),
				)
			);

		}

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
		if ( isset( $input['bento_publishable_key'] ) ) {
			$new_input['bento_publishable_key'] = sanitize_text_field( $input['bento_publishable_key'] );
		}
		if ( isset( $input['bento_secret_key'] ) ) {
			$new_input['bento_secret_key'] = sanitize_text_field( $input['bento_secret_key'] );
		}
		if ( isset( $input['bento_events_recurrence'] ) ) {
			$new_input['bento_events_recurrence'] = absint( sanitize_text_field( $input['bento_events_recurrence'] ) );
		}

		if ( isset( $input['bento_events_user_not_logged'] ) ) {
			$new_input['bento_events_user_not_logged'] = absint( sanitize_text_field( $input['bento_events_user_not_logged'] ) );
		}

		if ( isset( $input['bento_events_user_not_completed_content'] ) ) {
			$new_input['bento_events_user_not_completed_content'] = absint( sanitize_text_field( $input['bento_events_user_not_completed_content'] ) );
		}

		if ( isset( $input['bento_events_repeat_not_event'] ) ) {
			$new_input['bento_events_repeat_not_event'] = absint( sanitize_text_field( $input['bento_events_repeat_not_event'] ) );
		}

		// Add this new sanitization for the tracking option
		$new_input['bento_enable_tracking'] = isset( $input['bento_enable_tracking'] ) ? '1' : '0';

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

		if ( $args['type'] === 'checkbox' ) {
			printf(
				'<label><input type="checkbox" %s id="%s" name="bento_settings[%s]" value="1" %s /> %s</label>',
				$attributes_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_attr( $args['id'] ),
				esc_attr( $args['id'] ),
				checked( $args['value'], '1', false ),
				esc_html( $args['label'] )
			);
		} else {
			printf(
				'<input type="%s" %s id="%s" name="bento_settings[%s]" value="%s" />',
				esc_attr( $args['type'] ),
				$attributes_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_attr( $args['id'] ),
				esc_attr( $args['id'] ),
				esc_attr( $args['value'] )
			);
		}
	}
}

if ( is_admin() ) {
	$bento_settings_page = new BentoSettingsPage();
}
