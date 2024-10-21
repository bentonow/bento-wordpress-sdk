<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bento integration.
 *
 * @since 1.3.6
 */
class WPForms_Bento extends WPForms_Provider {

	/**
	 * Current form ID.
	 *
	 * @since 1.9.0.4
	 *
	 * @var int
	 */
	private $form_id = 0;

	/**
	 * Current entry ID.
	 *
	 * @since 1.9.0.4
	 *
	 * @var int
	 */
	private $entry_id = 0;

	/**
	 * Provider access token.
	 *
	 * @since 1.3.6
	 *
	 * @var string
	 */
	public $access_token;

	/**
	 * Provider API key.
	 *
	 * @since 1.3.6
	 *
	 * @var string
	 */
	public $api_key = 'nil';

	/**
	 * Initialize.
	 *
	 * @since 1.3.6
	 */
	public function init() {

		$this->version  = '1.3.6';
		$this->name     = 'Bento';
		$this->slug     = 'bento';
		$this->priority = 14;
		$this->icon     = 'https://app.bentonow.com/brand/bento-logo-3d.png';

		if ( is_admin() ) {
			// Admin notice requesting connecting.
			$this->connect_request();

			add_action( 'wpforms_admin_notice_dismiss_ajax', [ $this, 'connect_dismiss' ] );
			add_filter( "wpforms_providers_provider_settings_formbuilder_display_content_default_screen_{$this->slug}", [ $this, 'builder_settings_default_content' ] );

		}
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @since 1.3.6
	 *
	 * @param array $fields    List of fields with their data and settings.
	 * @param array $entry     Submitted entry values.
	 * @param array $form_data Form data and settings.
	 * @param int   $entry_id  Saved entry ID.
	 *
	 * @return void
	 */
	public function process_entry( $fields, $entry, $form_data, $entry_id = 0 ) {
        
		// Only run if this form has a connections for this provider.
		if ( empty( $form_data['providers'][ $this->slug ] ) ) {
			return;
		}

		/*
		 * Fire for each connection.
		 */

		foreach ( $form_data['providers'][ $this->slug ] as $connection ) :

			// Before proceeding make sure required fields are configured.
			if ( empty( $connection['fields']['email'] ) ) {
				continue;
			}

			// Setup basic data.
			$list_id    = $connection['list_id'];
			$account_id = $connection['account_id'];
			$email_data = explode( '.', $connection['fields']['email'] );
			$email_id   = $email_data[0];
			$email      = $fields[ $email_id ]['value'];

            # form title
            $event_name = $form_data['settings']['form_title'];

			// Check for conditionals.
			$pass = $this->process_conditionals( $fields, $entry, $form_data, $connection );

			if ( ! $pass ) {
				wpforms_log(
					sprintf( 'The Bento connection %s was not processed due to conditional logic.', $connection['name'] ?? '' ),
					$fields,
					[
						'type'    => [ 'provider', 'conditional_logic' ],
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					]
				);
				continue;
			}

			$this->form_id  = $form_data['id'] ?? 0;
			$this->entry_id = $entry_id;

            $custom_fields = [];

			/*
			 * Setup Merge Vars
			 */

			$merge_vars = [];

			foreach ( $connection['fields'] as $name => $merge_var ) {

				// Don't include Email or Full name fields.
				if ( 'email' === $name ) {
					continue;
				}

				// Check if merge var is mapped.
				if ( empty( $merge_var ) ) {
					continue;
				}

				$merge_var = explode( '.', $merge_var );
				$id        = $merge_var[0];
				$key       = ! empty( $merge_var[1] ) ? $merge_var[1] : 'value';

				// Check if mapped form field has a value.
				if ( empty( $fields[ $id ][ $key ] ) ) {
					continue;
				}

				$value = $fields[ $id ][ $key ];

				// Bento stores name in two fields, so we have to
				// separate it.
				if ( $name === 'full_name' ) {

					$names = explode( ' ', $value );

					if ( ! empty( $names[0] ) ) {
						$merge_vars['first_name'] = $names[0];
					}

					if ( ! empty( $names[1] ) ) {
						$merge_vars['last_name'] = $names[1];
					}

					continue;
				}

				$merge_vars[ $name ] = $value;
			}

			/*
			 * Process in API
			 */

             Bento_Events_Controller::trigger_event(
                get_current_user_id(),
                $event_name,
                $email,
                [],
                $merge_vars
            );


		endforeach;
	}

	/************************************************************************
	 * API methods - these methods interact directly with the provider API. *
	 ************************************************************************/

	/**
	 * Authenticate with the API.
	 *
	 * @since 1.3.6
	 *
	 * @param array  $data    Contact data.
	 * @param string $form_id Form ID.
	 *
	 * @return WP_Error|string Unique ID or error object
	 */
	public function api_auth( $data = [], $form_id = '' ) {

		$this->form_id      = (int) $form_id;
		$this->access_token = $data['authcode'] ?? '';
		$user               = $this->get_account_information();

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$id = uniqid();

		wpforms_update_providers_options(
			$this->slug,
			[
				'access_token' => sanitize_text_field( 'bento' ),
				'label'        => sanitize_text_field( 'bento' ),
				'date'         => time(),
			],
			$id
		);

		return $id;
	}

	/**
	 * Get account information.
	 *
	 * @since 1.7.6
	 *
	 * @return array|WP_Error
	 */
	public function get_account_information() {

		return [
			'website' => 'https://bentonow.com',
		];
	}

	/**
	 * Establish connection object to API.
	 *
	 * @since 1.3.6
	 *
	 * @param string $account_id
	 *
	 * @return mixed array or error object.
	 */
	public function api_connect( $account_id ) {

		if ( ! empty( $this->api[ $account_id ] ) ) {
			return $this->api[ $account_id ];
		} else {
			$providers = wpforms_get_providers_options();
			if ( ! empty( $providers[ $this->slug ][ $account_id ] ) ) {
				$this->api[ $account_id ] = true;
				$this->access_token       = $providers[ $this->slug ][ $account_id ]['access_token'];
			} else {
				return $this->error( 'API error' );
			}
		}
	}

	/**
	 * Retrieve provider account lists.
	 *
	 * @since 1.3.6
	 *
	 * @param string $connection_id
	 * @param string $account_id
	 *
	 * @return array|WP_Error array or error object
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		return [
			[
				"id" => "1",
				"name" => "Main",
			],
		];
	}

	/**
	 * Retrieve provider account list fields.
	 *
	 * @since 1.3.6
	 *
	 * @param string $connection_id
	 * @param string $account_id
	 * @param string $list_id
	 *
	 * @return mixed array or error object
	 */
	public function api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {

		$provider_fields = [
			[
				'name'       => 'Email',
				'field_type' => 'email',
				'req'        => '1',
				'tag'        => 'email',
			],
			[
				'name'       => 'Full Name',
				'field_type' => 'name',
				'tag'        => 'full_name',
			],
			[
				'name'       => 'First Name',
				'field_type' => 'first',
				'tag'        => 'first_name',
			],
			[
				'name'       => 'Last Name',
				'field_type' => 'last',
				'tag'        => 'last_name',
			],
			[
				'name'       => 'Phone',
				'field_type' => 'text',
				'tag'        => 'work_phone',
			],
			[
				'name'       => 'Website',
				'field_type' => 'text',
				'tag'        => 'url',
			],
		];

		return $provider_fields;
	}


	/*************************************************************************
	 * Output methods - these methods generally return HTML for the builder. *
	 *************************************************************************/

	/**
	 * Provider account authorize fields HTML.
	 *
	 * @since 1.3.6
	 *
	 * @return string
	 */
	public function output_auth() {

		$providers = wpforms_get_providers_options();
		$class     = ! empty( $providers[ $this->slug ] ) ? 'hidden' : '';

		ob_start();
		?>

		<div class="wpforms-provider-account-add <?php echo sanitize_html_class( $class ); ?> wpforms-connection-block">

			<h4><?php esc_html_e( 'Add New Account', 'wpforms-lite' ); ?></h4>

			<?php
			

			printf(
				'<button data-provider="%s">%s</button>',
				esc_attr( $this->slug ),
				esc_html__( 'Connect', 'wpforms-lite' )
			);

			?>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Provider account list groups HTML.
	 *
	 * @since 1.3.6
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $connection    Connection data.
	 *
	 * @return string
	 */
	public function output_groups( $connection_id = '', $connection = [] ) {

		// No groups or segments for this provider.
		return '';
	}

	/**
	 * Default content for the provider settings panel in the form builder.
	 *
	 * @since 1.6.8
	 *
	 * @param string $content Default content.
	 *
	 * @return string
	 */
	public function builder_settings_default_content( $content ) {

		ob_start();
		?>
		<?php

		return $content . ob_get_clean();
	}

	/*************************************************************************
	 * Integrations tab methods - these methods relate to the settings page. *
	 *************************************************************************/

	/**
	 * AJAX to add a provider from the settings integrations tab.
	 *
	 * @since 1.7.6
	 */
	public function integrations_tab_add() {

		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( $_POST['provider'] !== $this->slug ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$data = ! empty( $_POST['data'] ) ? wp_parse_args( wp_unslash( $_POST['data'] ), [] ) : [];

		if ( empty( $data['authcode'] ) ) {
			wp_send_json_error(
				[
					'error_msg' => esc_html__( 'The "Authorization Code" is required.', 'wpforms-lite' ),
				]
			);
		}

		if ( empty( $data['label'] ) ) {
			wp_send_json_error(
				[
					'error_msg' => esc_html__( 'The "Account Nickname" is required.', 'wpforms-lite' ),
				]
			);
		}

		parent::integrations_tab_add();
	}

	/**
	 * Form fields to add a new provider account.
	 *
	 * @since 1.3.6
	 */
	public function integrations_tab_new_form() {

		?>
		
		<?php
		printf(
			'<input type="text" name="authcode" placeholder="%s %s *" class="wpforms-required">',
			esc_attr( $this->name ),
			esc_attr__( 'Authorization Code', 'wpforms-lite' )
		);

		printf(
			'<input type="text" name="label" placeholder="%s %s *" class="wpforms-required">',
			esc_attr( $this->name ),
			esc_attr__( 'Account Nickname', 'wpforms-lite' )
		);
	}

	/************************
	 * Other functionality. *
	 ************************/

	/**
	 * Add admin notices to connect to Bento.
	 *
	 * @since 1.3.6
	 */
	public function connect_request() {

		// Only consider showing the review request to admin users.
		if ( ! is_super_admin() ) {
			return;
		}

		// Don't display on WPForms admin content pages.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['wpforms-page'] ) ) {
			return;
		}

		// Don't display if user is about to connect via Settings page.
		if ( ! empty( $_GET['wpforms-integration'] ) && $this->slug === $_GET['wpforms-integration'] ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Only display the notice if the Bento option is set and
		// there are previous Bento connections created.
		// Please do not delete 'wpforms_bento' option check from the code.
		$cc_notice = get_option( 'wpforms_bento', false );
		$providers = wpforms_get_providers_options();

		if ( ! $cc_notice || ! empty( $providers[ $this->slug ] ) ) {
			return;
		}

		// Output the notice message.
		$connect    = admin_url( 'admin.php?page=wpforms-settings&view=integrations&wpforms-integration=constant-contact#!wpforms-tab-providers' );
		$learn_more = admin_url( 'admin.php?page=wpforms-page&view=constant-contact' );

		ob_start();
		?>
		
		

		<style>
			.wpforms-constant-contact-notice p:first-of-type {
				margin: 16px 0 8px;
			}

			.wpforms-constant-contact-notice p:last-of-type {
				margin: 8px 0 16px;
			}

			.wpforms-constant-contact-notice .button-primary,
			.wpforms-constant-contact-notice .button-secondary {
				margin: 0 10px 0 0;
			}
		</style>
		<?php

		$notice = ob_get_clean();

		\WPForms\Admin\Notice::info(
			$notice,
			[
				'dismiss' => \WPForms\Admin\Notice::DISMISS_GLOBAL,
				'slug'    => 'constant_contact_connect',
				'autop'   => false,
				'class'   => 'wpforms-constant-contact-notice',
			]
		);
	}

	/**
	 * Dismiss the Bento admin notice.
	 *
	 * @since 1.3.6
	 * @since 1.6.7.1 Added parameter $notice_id.
	 *
	 * @param string $notice_id Notice ID (slug).
	 */
	public function connect_dismiss( $notice_id = '' ) {

		if ( $notice_id !== 'global-constant_contact_connect' ) {
			return;
		}

		delete_option( 'wpforms_bento' );

		wp_send_json_success();
	}

	/**
	 * Request to the Bento API.
	 *
	 * @since 1.9.0.4
	 *
	 * @param string $url  Request URL.
	 * @param array  $args Request arguments.
	 *
	 * @return array|WP_Error
	 */
	private function request( string $url, array $args = [] ) {

		$args['method']                   = $args['method'] ?? 'GET';
		$args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
		$args['headers']['Content-Type']  = 'application/json';

		if ( isset( $args['body'] ) ) {
			$args['body'] = wp_json_encode( $args['body'] );
		}

		$url      = add_query_arg( 'api_key', $this->api_key, $url );
		$response = wp_remote_request( $url, $args );
		$response = is_wp_error( $response ) ? $response : (array) $response;

		return $this->process_response( $response );
	}

	/**
	 * Process response.
	 *
	 * @since 1.9.0.4
	 *
	 * @param array|WP_Error $response Response.
	 *
	 * @return array|WP_Error
	 */
	public function process_response( $response ) {

		if ( is_wp_error( $response ) ) {
			$this->log_error( $response );

			return $response;
		}

		// Body may be set here to an array or null.
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body ) || isset( $body[0]['error_key'] ) ) {
			$error_message = $body[0]['error_message'] ?? '';
			$error         = new WP_Error( $this->slug . '_error', $error_message );

			$this->log_error( $error );

			return $error;
		}

		return $body;
	}

	/**
	 * Log error message.
	 *
	 * @since 1.9.0.4
	 *
	 * @param WP_Error $error Error.
	 *
	 * @return void
	 */
	public function log_error( WP_Error $error ) {

		wpforms_log(
			'Bento API Error',
			$error->get_error_message(),
			[
				'type'    => [ 'provider', 'error' ],
				'parent'  => $this->entry_id,
				'form_id' => $this->form_id,
			]
		);
	}
}

new WPForms_Bento();
