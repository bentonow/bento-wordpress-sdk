<?php
/**
 * Bento Events Controller
 *
 * @package BentoHelper
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bento_Events_Controller', false ) ) {
	/**
	 * Class Bento_Events_Controller
	 */
		class Bento_Events_Controller {
			const BENTO_API_EVENT_ENDPOINT        = 'https://app.bentonow.com/api/v1/batch/events';
			const EVENTS_QUEUE_OPTION_KEY         = 'bento_events_queue';
			const IS_SENDING_EVENTS_TRANSIENT_KEY = 'bento_sending_events';
			const EVENTS_QUEUE_CLEANUP_FLAG       = 'bento_events_queue_cleanup_done';

		/**
		 * Bento configuration options
		 *
		 * @var array
		 */
		private static $bento_options = array();

		/**
		 * Get a Bento configuration option
		 *
		 * @param string $option_name The option name.
		 * @return mixed The option value.
		 */
		public static function get_bento_option( $option_name ) {
			if ( empty( self::$bento_options ) ) {
				self::$bento_options = get_option( 'bento_settings' );
			}
			return self::$bento_options[ $option_name ] ?? null;
		}

		/**
		 * Enqueue an event to Bento.
		 *
		 * @param int    $user_id The user ID that generates the event.
		 * @param string $type The event type.
		 * @param string $email The email event is for.
		 * @param array  $details The event details.
		 */
	protected static function enqueue_event( $user_id, $type, $email, $details = array() ) {
		$sanitized_email   = self::sanitize_email_for_logging( $email );
		$sanitized_details = self::sanitize_details_for_logging( $details );

		Bento_Logger::info( "Dispatching event immediately - Type: {$type}, Email: {$sanitized_email}, User ID: {$user_id}, Details: {$sanitized_details}" );

		$sent = self::send_event( $user_id, $type, $email, $details );

		if ( ! $sent ) {
			Bento_Logger::error( "Event failed to send and will not be re-queued - Type: {$type}, Email: {$sanitized_email}" );
		}
	}

		public static function trigger_event($user_id, $type, $email, $details = array(), $custom_fields = array()) {
			return self::send_event($user_id, $type, $email, $details, $custom_fields);
		}

		/**
		 * Send an event to Bento.
		 *
		 * @param int    $user_id The user ID that generates the event.
		 * @param string $type    The event type.
		 * @param string $email   The email event is for.
		 * @param array  $details The event details.
		 *
		 * @return bool True if event was sent successfully.
		 */
		protected static function send_event( $user_id, $type, $email, $details = array(), $custom_fields = array() ) {
			$bento_site_key = self::get_bento_option( 'bento_site_key' );
			$bento_publishable_key = self::get_bento_option( 'bento_publishable_key' );
			$bento_secret_key = self::get_bento_option( 'bento_secret_key' );
			
			$sanitized_email = self::sanitize_email_for_logging( $email );
			
			if ( empty( $bento_site_key ) || empty( $bento_publishable_key ) || empty( $bento_secret_key ) ) {
				Bento_Logger::error( "Missing Bento API credentials - cannot send event type: {$type} for email: {$sanitized_email}" );
				return false;
			}
			
			Bento_Logger::info( "Sending event to Bento API - Type: {$type}, Email: {$sanitized_email}, User ID: {$user_id}" );

			$api_url = 'https://' . $bento_publishable_key . ':' . $bento_secret_key . '@' . substr(self::BENTO_API_EVENT_ENDPOINT, 8) . '?site_uuid=' . $bento_site_key;

			$data = array(
				'events' => array(
					array(
						'type'  => $type,
						'email' => $email,
					)
				)
			);

			if (!empty($details)) {
				$data['events'][0]['details'] = $details;
			}

			$user_fields = self::get_user_fields($user_id);

			if (!empty($user_fields)) {
				$data['events'][0]['fields'] = $user_fields;
			}

			if (!empty($custom_fields)) {
				if (isset($data['events'][0]['fields']) && is_array($data['events'][0]['fields'])) {
					$data['events'][0]['fields'] = array_merge($data['events'][0]['fields'], $custom_fields);
				} else {
					$data['events'][0]['fields'] = $custom_fields;
				}
			}

			$response = wp_remote_post(
				$api_url,
				array(
					'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					'body'        => wp_json_encode( $data ),
					'method'      => 'POST',
					'data_format' => 'body',
				)
			);

			$response_body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );
			
			if ( is_wp_error( $response ) ) {
				Bento_Logger::error( "Event send failed - Type: {$type}, Email: {$sanitized_email}, Error: " . $response->get_error_message() );
				return false;
			}
			
			// Always assume success and clear from queue, but log response for debugging
			Bento_Logger::info( "Event sent (assuming success) - Type: {$type}, Email: {$sanitized_email}, Response code: {$response_code}, Body: " . substr( $response_body, 0, 200 ) );

			return true;
		}

		/**
		 * Get User Fields
		 *
		 * @param int $user_id The user ID.
		 * @return array The user fields.
		 */
		private static function get_user_fields( $user_id ) {
			if ( empty( $user_id ) ) {
				return array();
			}

			$user = get_user_by( 'id', absint( $user_id ) );

			if ( empty( $user ) ) {
				$fields = array(
					'user_roles'   => 'guest',
				);
				return $fields;
			}

			$fields = array(
				'first_name'   => $user->first_name,
				'last_name'    => $user->last_name,
				'display_name' => $user->display_name,
				'user_login'   => $user->user_login,
				'user_id'      => $user->ID,
				'user_roles'   => implode( ',', $user->roles ),
				'user_email'   => $user->user_email,
			);

			return $fields;
		}

		/**
		 * Init class
		 *
		 *  @return void
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'load_events_controllers' ) );
			add_action( 'bento_send_events_hook', array( __CLASS__, 'bento_send_events_hook' ) );

			self::disable_legacy_event_queue_cron();
		}

		/**
		 * Unschedule the Bento events cron job. Used when the plugin is deactivated.
		 */
		public static function remove_cron_jobs() {
			self::disable_legacy_event_queue_cron();
		}

		/**
		 * Disable the legacy cron job and clean up queued data.
		 */
		private static function disable_legacy_event_queue_cron() {
			wp_clear_scheduled_hook( 'bento_send_events_hook' );

			if ( get_option( self::EVENTS_QUEUE_CLEANUP_FLAG ) ) {
				return;
			}

			Bento_Logger::info( 'Cleaning up legacy Bento events queue artifacts.' );
			self::cleanup_legacy_event_queue();

			update_option( self::EVENTS_QUEUE_CLEANUP_FLAG, time() );
		}

		/**
		 * Remove legacy queue options and transients.
		 */
		private static function cleanup_legacy_event_queue() {
			delete_option( self::EVENTS_QUEUE_OPTION_KEY );
			delete_transient( self::EVENTS_QUEUE_OPTION_KEY );
			delete_transient( self::EVENTS_QUEUE_OPTION_KEY . '_temp_keys' );
		}


		/**
		 * Load events controllers
		 *
		 * @return void
		 */
		public static function load_events_controllers() {
			$controllers = array(
				'class-wp-bento-events',
				'class-learndash-bento-events',
				'class-woocommerce-bento-events',
				'class-surecart-bento-events',
				'class-woocommerce-subscriptions-bento-events',
				'class-edd-bento-events',
			);

			foreach ( $controllers as $controller ) {
				require_once 'events-controllers/' . $controller . '.php';
			}
		}

		/**
		 * Sanitize email for logging to prevent PII exposure
		 *
		 * @param string $email The email to sanitize.
		 * @return string Sanitized email for logging.
		 */
		private static function sanitize_email_for_logging( $email ) {
			if ( empty( $email ) || ! is_email( $email ) ) {
				return '[invalid-email]';
			}
			
			$parts = explode( '@', $email );
			if ( count( $parts ) !== 2 ) {
				return '[malformed-email]';
			}
			
			$username = $parts[0];
			$domain = $parts[1];
			
			// Handle short usernames properly
			$username_length = strlen( $username );
			if ( $username_length === 0 ) {
				$masked_username = '*';
			} elseif ( $username_length === 1 ) {
				$masked_username = $username . '*';
			} else {
				// Show first 2 chars of username, mask the rest
				$masked_username = substr( $username, 0, 2 ) . str_repeat( '*', $username_length - 2 );
			}
			
			return $masked_username . '@' . $domain;
		}

		/**
		 * Sanitize event details for logging to prevent PII exposure
		 *
		 * @param array $details The event details to sanitize.
		 * @return string Sanitized details summary for logging.
		 */
		private static function sanitize_details_for_logging( $details ) {
			if ( empty( $details ) || ! is_array( $details ) ) {
				return '[no-details]';
			}
			
			$safe_keys = array();
			$sensitive_patterns = array( 'email', 'phone', 'address', 'name', 'first_name', 'last_name', 'user_login' );
			
			foreach ( $details as $key => $value ) {
				$is_sensitive = false;
				foreach ( $sensitive_patterns as $pattern ) {
					if ( stripos( $key, $pattern ) !== false ) {
						$is_sensitive = true;
						break;
					}
				}
				
				if ( ! $is_sensitive ) {
					$safe_keys[] = $key;
				}
			}
			
			return '[keys: ' . implode( ', ', $safe_keys ) . ']';
		}
		

	}
}
