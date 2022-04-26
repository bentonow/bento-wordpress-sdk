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
		const BENTO_API_EVENT_ENDPOINT = 'https://app.bentonow.com/tracking/generic';
		const EVENTS_QUEUE_OPTION_KEY  = 'bento_events_queue';

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
		protected function enqueue_event( $user_id, $type, $email, $details = array() ) {
			if ( empty( self::get_bento_option( 'bento_events_recurrence' ) ) ) {
				delete_option( self::EVENTS_QUEUE_OPTION_KEY );
				return;
			}

			$events_queue   = get_option( self::EVENTS_QUEUE_OPTION_KEY, array() );
			$events_queue[] = array(
				'user_id' => $user_id,
				'type'    => $type,
				'email'   => $email,
				'details' => $details,
			);
			update_option( self::EVENTS_QUEUE_OPTION_KEY, $events_queue );
		}

		/**
		 * Send an event to Bento.
		 *
		 * @param int    $user_id The user ID that generates the event.
		 * @param string $type The event type.
		 * @param string $email The email event is for.
		 * @param array  $details The event details.
		 * @return bool True if event was sent successfully.
		 */
		private function send_event( $user_id, $type, $email, $details = array() ) {
			$bento_options  = get_option( 'bento_settings' );
			$bento_site_key = $bento_options['bento_site_key'];

			if ( empty( $bento_site_key ) ) {
				return;
			}

			$data = array(
				'site'    => $bento_site_key,
				'type'    => $type,
				'email'   => $email,
				'details' => $details,
				'fields'  => $this->get_user_fields( $user_id ),
			);

			$response      = wp_remote_post(
				self::BENTO_API_EVENT_ENDPOINT,
				array(
					'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					'body'        => wp_json_encode( $data ),
					'method'      => 'POST',
					'data_format' => 'body',
				)
			);
			$response_body = wp_remote_retrieve_body( $response );

			return 'OK' === $response_body;
		}

		/**
		 * Get User Fields
		 *
		 * @param int $user_id The user ID.
		 * @return array The user fields.
		 */
		private function get_user_fields( $user_id ) {
			if ( empty( $user_id ) ) {
				return array();
			}

			$user = get_user_by( 'id', absint( $user_id ) );
			if ( empty( $user ) ) {
				return array();
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
		}

		/**
		 * Load events controllers
		 *
		 * @return void
		 */
		public static function load_events_controllers() {
			$controllers = array(
				'class-learndash-bento-events',
			);

			foreach ( $controllers as $controller ) {
				require_once 'events-controllers/' . $controller . '.php';
			}
		}

	}
}
