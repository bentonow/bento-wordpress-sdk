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
		const BENTO_API_EVENT_ENDPOINT        = 'https://app.bentonow.com/tracking/generic';
		const EVENTS_QUEUE_OPTION_KEY         = 'bento_events_queue';
		const IS_SENDING_EVENTS_TRANSIENT_KEY = 'bento_sending_events';

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
			if ( empty( self::get_bento_option( 'bento_events_recurrence' ) ) ) {
				delete_option( self::EVENTS_QUEUE_OPTION_KEY );
				return;
			}

			if ( ! self::is_sending_events() ) {
				$events_queue = get_option( self::EVENTS_QUEUE_OPTION_KEY, array() );
			} else {
				$events_queue = get_transient( self::EVENTS_QUEUE_OPTION_KEY );
				if ( ! $events_queue ) {
					$events_queue = array();
				}
			}

			$events_queue[] = array(
				'user_id' => $user_id,
				'type'    => $type,
				'email'   => $email,
				'details' => $details,
			);

			if ( ! self::is_sending_events() ) {
				update_option( self::EVENTS_QUEUE_OPTION_KEY, $events_queue );
			} else {
				set_transient( self::EVENTS_QUEUE_OPTION_KEY, $events_queue, DAY_IN_SECONDS );
			}

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
		protected static function send_event( $user_id, $type, $email, $details = array() ) {
			$bento_site_key = self::get_bento_option( 'bento_site_key' );
			if ( empty( $bento_site_key ) ) {
				return;
			}

			$data = array(
				'site'    => $bento_site_key,
				'type'    => $type,
				'email'   => $email,
				'details' => $details,
				'fields'  => self::get_user_fields( $user_id ),
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
		private static function get_user_fields( $user_id ) {
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

			$interval = self::get_bento_option( 'bento_events_recurrence' );
			if ( ! empty( $interval ) ) {
				// add cron job to send events to Bento.
				add_filter( 'cron_schedules', array( __CLASS__, 'add_events_cron_interval' ) ); // phpcs:ignore WordPress.WP.CronInterval
				add_action( 'bento_send_events_hook', array( __CLASS__, 'bento_send_events_hook' ) );

				if ( ! wp_next_scheduled( 'bento_send_events_hook' ) ) {
					wp_schedule_event( time(), 'bento_send_events_interval', 'bento_send_events_hook' );
				}
			} else {
				self::remove_cron_jobs();
			}
		}

		/**
		 * Unschedule the Bento events cron job. Used when the plugin is deactivated.
		 */
		public static function remove_cron_jobs() {
			$send_events_timestamp = wp_next_scheduled( 'bento_send_events_hook' );
			wp_unschedule_event( $send_events_timestamp, 'bento_send_events_hook' );
		}

		/**
		 * Check if bento is sending events.
		 */
		private static function is_sending_events() {
			return true === get_transient( self::IS_SENDING_EVENTS_TRANSIENT_KEY );
		}

		/**
		 * Send events in the queue to Bento.
		 */
		public static function bento_send_events_hook() {
			if ( self::is_sending_events() ) {
				WP_DEBUG && error_log( '[Bento] - Bento is already sending events.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return; // Already sending events.
			}
			WP_DEBUG && error_log( '[Bento] - Sending Bento events.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			// set the transient to true so we know we're sending events.
			set_transient( self::IS_SENDING_EVENTS_TRANSIENT_KEY, true, HOUR_IN_SECONDS * 6 );

			$events_queue     = get_option( self::EVENTS_QUEUE_OPTION_KEY, array() );
			$new_events_queue = array();
			foreach ( $events_queue as $event ) {
				$event_status = self::send_event( $event['user_id'], $event['type'], $event['email'], $event['details'] );
				WP_DEBUG && error_log( '[Bento] - Bento event "' . $event['type'] . '" sended. Status: ' . $event_status ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				if ( ! $event_status ) {
					// event was not sent successfully.
					$new_events_queue[] = $event;
				}
			}

			// merge temporary queue with the permanent queue.
			$temporary_queue = get_transient( self::EVENTS_QUEUE_OPTION_KEY );
			if ( ! empty( $temporary_queue ) && is_array( $temporary_queue ) ) {
				$new_events_queue = array_merge( $new_events_queue, $temporary_queue );
			}
			delete_transient( self::EVENTS_QUEUE_OPTION_KEY );
			update_option( self::EVENTS_QUEUE_OPTION_KEY, $new_events_queue );

			// finish sending events.
			delete_transient( self::IS_SENDING_EVENTS_TRANSIENT_KEY );
			WP_DEBUG && error_log( '[Bento] - Bento events sended.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		/**
		 * Add bento cron interval.
		 *
		 * @param array $schedule The schedule.
		 * @return array
		 */
		public static function add_events_cron_interval( $schedule ) {
			$interval = self::get_bento_option( 'bento_events_recurrence' );
			if ( empty( $interval ) ) {
				return $schedule;
			}

			$schedule['bento_send_events_interval'] = array(
				'interval' => MINUTE_IN_SECONDS * $interval,
				'display'  => __( 'Bento Send Events Interval', 'bentonow' ),
			);

			return $schedule;
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
				'class-woocommerce-subscriptions-bento-events',
				'class-edd-bento-events',
			);

			foreach ( $controllers as $controller ) {
				require_once 'events-controllers/' . $controller . '.php';
			}
		}

	}
}
