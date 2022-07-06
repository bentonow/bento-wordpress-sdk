<?php
/**
 * WP Bento Events Controller
 *
 * @package BentoHelper
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Bento_Events', false ) ) {
	/**
	 * LearnDash Bento Events
	 */
	class WP_Bento_Events extends Bento_Events_Controller {
		const BENTO_LAST_LOGIN_META_KEY            = 'bento_last_login';
		const BENTO_LAST_LOGIN_EVENT_SENT_META_KEY = 'bento_last_login_event_sent';

		/**
		 * Init class
		 *
		 *  @return void
		 */
		public function __construct() {
			add_action( 'wp_login', array( $this, 'save_user_last_login_meta' ), 10, 2 );

			$bento_user_not_logged_interval = self::get_bento_option( 'bento_events_user_not_logged' );
			if ( ! empty( $bento_user_not_logged_interval ) ) {
				// add cron job to send login events.
				add_action( 'bento_verify_logins_hook', array( __CLASS__, 'bento_verify_logins_hook' ) );

				if ( ! wp_next_scheduled( 'bento_verify_logins_hook' ) ) {
					wp_schedule_event( time(), 'hourly', 'bento_verify_logins_hook' );
				}
			} else {
				self::remove_cron_jobs();
			}
		}

		/**
		 * Verify last login date and send event.
		 */
		public static function bento_verify_logins_hook() {
			$bento_user_not_logged_interval = self::get_bento_option( 'bento_events_user_not_logged' );
			$bento_repeat_not_events        = self::get_bento_option( 'bento_events_repeat_not_event' );

			if ( empty( $bento_user_not_logged_interval ) ) {
				WP_DEBUG && error_log( '[Bento] - User not logged events disabled.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			WP_DEBUG && error_log( '[Bento] - Checking user logins.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$users = get_users(
				array(
					'meta_key'     => self::BENTO_LAST_LOGIN_META_KEY,  // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_compare' => '<',
					'meta_value'   => strtotime( "-$bento_user_not_logged_interval day" ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);

			if ( ! empty( $users ) ) {
				foreach ( $users as $user ) {
					$last_event_sent = get_user_meta( $user->ID, self::BENTO_LAST_LOGIN_EVENT_SENT_META_KEY, true );
					if ( ! empty( $last_event_sent ) && ( empty( $bento_repeat_not_events ) || $last_event_sent > strtotime( "-$bento_repeat_not_events day" ) ) ) {
						continue;
					}

					// send event.
					self::enqueue_event(
						$user->ID,
						'user_not_logged_since',
						$user->user_email,
						array(
							'last_login' => get_user_meta( $user->ID, self::BENTO_LAST_LOGIN_META_KEY, true ),
						)
					);
					// set last event sent.
					update_user_meta( $user->ID, self::BENTO_LAST_LOGIN_EVENT_SENT_META_KEY, time() );
				}
			}

			WP_DEBUG && error_log( '[Bento] - User logins checked.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		/**
		 * Unschedule the Bento events cron job. Used when the plugin is deactivated.
		 */
		public static function remove_cron_jobs() {
			$send_events_timestamp = wp_next_scheduled( 'bento_verify_logins_hook' );
			wp_unschedule_event( $send_events_timestamp, 'bento_verify_logins_hook' );
		}

		/**
		 * Save user last login meta
		 *
		 * @param string $user_login The user login.
		 * @param object $user The user object.
		 */
		public function save_user_last_login_meta( $user_login, $user ) {
			update_user_meta( $user->ID, self::BENTO_LAST_LOGIN_META_KEY, time() );
		}

	}
	new WP_Bento_Events();
}
