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
        // Add AJAX handler for the test email
        add_action('wp_ajax_bento_send_test_email', array($this, 'send_test_email'));

        // Add script to admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9Im5vbmUiIHZpZXdCb3g9IjAgMCA0OCA0OCIgaWQ9IlJpY2ViYWxsLS1TdHJlYW1saW5lLVBsdW1wIiBoZWlnaHQ9IjIwIiB3aWR0aD0iMjAiPjxkZXNjPlJpY2ViYWxsIFN0cmVhbWxpbmUgSWNvbjogaHR0cHM6Ly9zdHJlYW1saW5laHEuY29tPC9kZXNjPjxnIGlkPSJyaWNlYmFsbC0tcmljZWJhbGwtamFwYW5lc2Utb25pZ2lyaS1zZWF3ZWVkIj48cGF0aCBpZD0iU3VidHJhY3QiIGZpbGw9IiNmMGY2ZmMiIGZpbGwtcnVsZT0iZXZlbm9kZCIgZD0iTTE0LjU1NCA2Ljc3MkMxNi44OTYxIDMuOTE1NjkgMjAuNDMzOCAyLjUgMjQgMi41YzMuNTY2MiAwIDcuMTAzOSAxLjQxNTY5IDkuNDQ2IDQuMjcyIDIuODg0MiAzLjUxNzMgNy4xOTE3IDkuMzU2OSAxMS4xNjgyIDE3LjA0NTMgMS4zMDA5IDIuNTE1MiAxLjg4NTggNS4zMDI0IDEuODg1OCA4LjA3MjYgMCA2LjU2MTEgLTQuMjY0OCAxMi40MDcxIC0xMS4wMDI1IDEzLjE0OTkgLTAuMDM1NCAtNi43NyAtMC40MzY0IC0xMi4zMTkxIC0wLjcyMTcgLTE1LjQ0NzYgLTAuMTk4MiAtMi4xNzI4IC0xLjQ1NTQgLTQuMzU5MiAtMy44NDA2IC01LjEwODhDMjkuMzAxNyAyMy45NyAyNi45ODgzIDIzLjUgMjQgMjMuNXMtNS4zMDE3IDAuNDcgLTYuOTM1MiAwLjk4MzRjLTIuMzg1MiAwLjc0OTYgLTMuNjQyNCAyLjkzNiAtMy44NDA2IDUuMTA4OCAtMC4yODUzIDMuMTI4NSAtMC42ODYzIDguNjc3NiAtMC43MjE3IDE1LjQ0NzZDNS43NjQ3NyA0NC4yOTcgMS41IDM4LjQ1MSAxLjUgMzEuODg5OWMwIC0yLjc3MDIgMC41ODQ4OSAtNS41NTc0IDEuODg1NzkgLTguMDcyNkM3LjM2MjMyIDE2LjEyODkgMTEuNjY5OCAxMC4yODkzIDE0LjU1NCA2Ljc3MlptMC45NDc1IDM4LjQ5MDZjMi40NDI5IDAuMTQ1MyA1LjI2NiAwLjIzNzQgOC40OTg1IDAuMjM3NHM2LjA1NTYgLTAuMDkyMSA4LjQ5ODUgLTAuMjM3NGMtMC4wMjc2IC02Ljc1NzcgLTAuNDI3NyAtMTIuMzAwMSAtMC43MTAzIC0xNS4zOTc5IC0wLjExODYgLTEuMzAwMiAtMC44MTA2IC0yLjIyMzMgLTEuNzUyNCAtMi41MTkzQzI4LjY3OTEgMjYuOTE5IDI2LjY2ODggMjYuNSAyNCAyNi41cy00LjY3OTEgMC40MTkgLTYuMDM1OCAwLjg0NTRjLTAuOTQxOCAwLjI5NiAtMS42MzM4IDEuMjE5MSAtMS43NTI0IDIuNTE5MyAtMC4yODI2IDMuMDk3OCAtMC42ODI3IDguNjQwMiAtMC43MTAzIDE1LjM5NzlaIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIHN0cm9rZS13aWR0aD0iMSI+PC9wYXRoPjwvZz48L3N2Zz4='
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<img src="https://bentonow.com/characters/no-messages.png" alt="Bento Character" style="float: right; margin-left: 20px; max-width: 200px;">
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

    public function enqueue_admin_scripts($hook) {
        // Only load on our settings page
        if('toplevel_page_bento-setting-admin' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'bento-admin-js',  // Changed handle to match localization
            plugins_url('assets/js/admin.js', dirname(__FILE__)),
            ['jquery'],
            bento_helper()->version,
            true
        );

        wp_localize_script(
            'bento-admin-js',
            'bentoAdminSettings',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bento_test_email'),
                'sending' => esc_html__('Sending...', 'bentonow'),
                'send' => esc_html__('Send Test Email', 'bentonow')
            ]
        );
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
			esc_html__( 'Connection', 'bentonow' ), // Title.
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


		add_settings_field(
			'bento_enable_tracking', // ID
			esc_html__( 'User Tracking', 'bentonow' ), // Title
			array( $this, 'bento_setting_field_callback' ), // Callback
			'bento-setting-admin', // Page
			'bento_setting_section_id', // Section
			array(
				'id'    => 'bento_enable_tracking',
				'value' => $this->options['bento_enable_tracking'] ?? '0',
				'type'  => 'checkbox',
				'label' => esc_html__( 'Enable Bento site tracking and Bento.js', 'bentonow' ),
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

      add_settings_field(
          'bento_enable_logging',
          esc_html__('Debug Logging', 'bentonow'),
          array($this, 'bento_setting_field_callback'),
          'bento-setting-admin',
          'bento_setting_section_id',
          array(
              'id'    => 'bento_enable_logging',
              'value' => $this->options['bento_enable_logging'] ?? '0',
              'type'  => 'checkbox',
              'label' => esc_html__('Enable plugin logging', 'bentonow'),
          )
      );


        // Transactional email settings

        add_settings_section(
            'bento_email_section_id', // ID
            esc_html__( 'Transactional Email Settings', 'bentonow' ), // Title
            function() {
                echo '<p>' . esc_html__( "Configure Transactional emails to be sent via Bento.") .'<br><br>' . "Bento Transactional Email API is designed to send <b>low volume emails from plugins (such as password resets, order notifications, etc),</b> it is not designed for high volume/frequent sending (such as newsletter plugins). Please use Bento's main application for that activity to avoid the aggressive rate limits that we've put in place to stop abuse.<br /><br />Please be aware that bento does not support email attachements of any kind at this time. <br /> Emails with attachments will continue to use the email provider configured in wp_mail. <br />You can read the quick setup guide here <a href='https://docs.bentonow.com/migrations/wordpress_transactional'>https://docs.bentonow.com/</a>" . '</p>';
            }, // Callback
            'bento-setting-admin' // Page
        );

        add_settings_field(
            'bento_enable_transactional',
            esc_html__( 'Transactional Emails', 'bentonow' ),
            array( $this, 'bento_setting_field_callback' ),
            'bento-setting-admin',
            'bento_email_section_id',
            array(
                'id'    => 'bento_enable_transactional',
                'value' => $this->options['bento_enable_transactional'] ?? '0',
                'type'  => 'checkbox',
                'label' => esc_html__( 'Send transactional emails via Bento', 'bentonow' ),
            )
        );

        add_settings_field(
            'bento_transactional_override',
            esc_html__( 'Transactional Override', 'bentonow' ),
            array( $this, 'bento_setting_field_callback' ),
            'bento-setting-admin',
            'bento_email_section_id',
            array(
                'id'    => 'bento_transactional_override',
                'value' => $this->options['bento_transactional_override'] ?? '0',
                'type'  => 'checkbox',
                'label' => esc_html__( 'Send emails to all users including unsubscribes in Bento. Use with caution', 'bentonow' ),
            )
        );

        add_settings_field(
            'bento_from_email',
            esc_html__('Bento Author', 'bentonow'),
            array($this, 'render_authors_dropdown'),
            'bento-setting-admin',
            'bento_email_section_id'
        );

        add_settings_field(
            'bento_test_email',
            esc_html__('Test Email', 'bentonow'),
            array($this, 'render_test_email_button'),
            'bento-setting-admin',
            'bento_email_section_id'
        );

        // Add AJAX handler for fetching authors
        add_action('wp_ajax_bento_fetch_authors', array($this, 'fetch_authors_ajax'));

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

      if ( isset( $input['bento_enable_logging'] ) ) {
          $new_input['bento_enable_logging'] = isset($input['bento_enable_logging']) ? '1' : '0';
      }
		// Add this new sanitization for the tracking option
		$new_input['bento_enable_tracking'] = isset( $input['bento_enable_tracking'] ) ? '1' : '0';

        $new_input['bento_transactional_override'] = isset( $input['bento_transactional_override'] ) ? '1' : '0';

        if ( isset( $input['bento_enable_transactional'] ) ) {
            $new_input['bento_enable_transactional'] = '1';
        }

        if ( isset( $input['bento_from_email'] ) ) {
            $new_input['bento_from_email'] = sanitize_email( $input['bento_from_email'] );
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

    public function render_test_email_button() {
        ?>
        <button type="button" id="bento-test-email" class="button button-secondary">
            <?php esc_html_e('Send Test Email', 'bentonow'); ?>
        </button>
        <br />
        <span class="description">
            <?php esc_html_e('Sends a test email to the admin email address.', 'bentonow'); ?>
        </span>
        <div id="bento-test-email-result" class="notice" style="display: none; margin-top: 10px;"></div>
        <?php
    }

    /**
     * Render the authors dropdown
     */
    public function render_authors_dropdown() {
        $current_value = $this->options['bento_from_email'] ?? '';
        $has_credentials = $this->has_valid_credentials();

        ?>
        <select id="bento_from_email" name="bento_settings[bento_from_email]" <?php echo !$has_credentials ? 'disabled' : ''; ?>>
            <?php if (!$has_credentials): ?>
                <option value=""><?php esc_html_e('Please save API credentials first', 'bentonow'); ?></option>
            <?php else: ?>
                <option value=""><?php esc_html_e('Loading authors...', 'bentonow'); ?></option>
            <?php endif; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the author email for sending transactional emails.', 'bentonow'); ?>
        </p>

        <?php if ($has_credentials): ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    var currentValue = <?php echo json_encode($current_value); ?>;

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bento_fetch_authors',
                            nonce: '<?php echo wp_create_nonce('bento_fetch_authors'); ?>'
                        },
                        success: function(response) {
                            var $select = $('#bento_from_email');
                            $select.empty();

                            if (response.success && response.data && response.data.data && response.data.data.length > 0) {
                                // Add default option
                                $select.append($('<option>', {
                                    value: '',
                                    text: '<?php esc_html_e('Select an author', 'bentonow'); ?>'
                                }));

                                // Add each author option
                                response.data.data.forEach(function(author) {
                                    var $option = $('<option>', {
                                        value: author.attributes.email,
                                        text: author.attributes.name + ' (' + author.attributes.email + ')'
                                    });

                                    if (author.attributes.email === currentValue) {
                                        $option.prop('selected', true);
                                    }

                                    $select.append($option);
                                });
                            } else {
                                $select.append($('<option>', {
                                    value: '',
                                    text: '<?php esc_html_e('No authors found', 'bentonow'); ?>'
                                }));
                            }
                        },
                        error: function() {
                            var $select = $('#bento_from_email');
                            $select.empty().append($('<option>', {
                                value: '',
                                text: '<?php esc_html_e('Error loading authors', 'bentonow'); ?>'
                            }));
                        }
                    });
                });
            </script>
        <?php endif;
    }

    /**
     * AJAX handler for fetching authors
     */
    public function fetch_authors_ajax() {
        check_ajax_referer('bento_fetch_authors', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $authors = $this->fetch_authors();

        if (is_wp_error($authors)) {
            wp_send_json_error($authors->get_error_message());
            return;
        }

        wp_send_json_success($authors);
    }

    /**
     * Fetch authors from Bento API
     */
    private function fetch_authors() {
        $options = get_option('bento_settings');
        $site_key = $options['bento_site_key'];
        $publishable_key = $options['bento_publishable_key'];
        $secret_key = $options['bento_secret_key'];

        $auth = base64_encode($publishable_key . ':' . $secret_key);

        $response = wp_remote_get(
            'https://app.bentonow.com/api/v1/fetch/authors?site_uuid=' . urlencode($site_key),
            array(
                'headers' => array(
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $auth,
                    'User-Agent' => 'bento-wordpress-'.$site_key,
                    'X-Bento-WP-Plug-Version' => bento_helper()->version
                ),
                'timeout' => 15
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['data'])) {
            return new WP_Error('no_authors', __('No authors found', 'bentonow'));
        }

        return $data;
    }

    /**
     * Check if we have valid credentials to make API calls
     */
    private function has_valid_credentials() {
        $options = get_option('bento_settings');
        return !empty($options['bento_site_key']) &&
            !empty($options['bento_publishable_key']) &&
            !empty($options['bento_secret_key']);
    }

    /**
     * Handle sending test email via AJAX request
     */
    public function send_test_email() {
        // Exit early if not an AJAX request
        if (!wp_doing_ajax()) {
            return;
        }

        check_ajax_referer('bento_test_email', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'bentonow')]);
            exit;
        }

        $options = get_option('bento_settings');

        // Check if transactional emails are enabled
        if (empty($options['bento_enable_transactional'])) {
            wp_send_json_error(['message' => __('Transactional emails are not enabled in Bento settings.', 'bentonow')]);
            exit;
        }

        // Check required settings
        if (empty($options['bento_site_key']) || empty($options['bento_publishable_key']) || empty($options['bento_secret_key'])) {
            wp_send_json_error(['message' => __('Missing Bento API credentials.', 'bentonow')]);
            exit;
        }

        // Get admin email
        $admin_email = get_option('admin_email');
        $from_email = $options['bento_from_email'] ?? $admin_email;

        // Create authorization header
        $auth = base64_encode($options['bento_publishable_key'] . ':' . $options['bento_secret_key']);

        // Format the request
        $api_url = 'https://app.bentonow.com/api/v1/batch/emails?site_uuid=' . $options['bento_site_key'];

        $body = [
            'emails' => [
                [
                    'to' => $admin_email,
                    'from' => $from_email,
                    'subject' => 'Bento Test Email',
                    'html_body' => 'This is a test email from your WordPress site. If you received this, Bento is working!',
                    'transactional' => true
                ]
            ]
        ];

        $response = wp_remote_post(
            $api_url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $auth,
                    'User-Agent' => 'bento-wordpress-'.$options['bento_site_key'],
                    'X-Bento-WP-Plug-Version' => bento_helper()->version
                ],
                'body' => wp_json_encode($body),
                'timeout' => 30,
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => 'API Error: ' . $response->get_error_message()
            ]);
            exit;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            wp_send_json_error([
                'message' => sprintf(
                    'API Error (%d): %s',
                    $response_code,
                    $response_body
                )
            ]);
            exit;
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Test email sent to %s. Please check your inbox.', 'bentonow'),
                $admin_email
            )
        ]);
        exit;
    }
}

if ( is_admin() ) {
	$bento_settings_page = new BentoSettingsPage();
}
