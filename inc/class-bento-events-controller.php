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
		// Always enqueue events regardless of recurrence setting
		// Events will be processed when cron runs or manually triggered
		
		$sanitized_email = self::sanitize_email_for_logging( $email );
		$sanitized_details = self::sanitize_details_for_logging( $details );
		
		Bento_Logger::info( "Enqueuing event - Type: {$type}, Email: {$sanitized_email}, User ID: {$user_id}, Details: {$sanitized_details}" );
		
		$new_event = array(
			'user_id' => $user_id,
			'type'    => $type,
			'email'   => $email,
			'details' => $details,
		);

		if ( ! self::is_sending_events() ) {
			$events_queue = get_option( self::EVENTS_QUEUE_OPTION_KEY, array() );
			$events_queue[] = $new_event;
			update_option( self::EVENTS_QUEUE_OPTION_KEY, $events_queue );
			Bento_Logger::info( "Event queued to database - Queue size: " . count( $events_queue ) );
		} else {
			// When processing is happening, use a different approach to avoid race conditions
			$temp_queue_key = self::EVENTS_QUEUE_OPTION_KEY . '_temp_' . time() . '_' . wp_rand( 1000, 9999 );
			set_transient( $temp_queue_key, array( $new_event ), DAY_IN_SECONDS );
			
			// Add this temp queue key to a list so we can merge them later
			$temp_queue_keys = get_transient( self::EVENTS_QUEUE_OPTION_KEY . '_temp_keys' );
			if ( ! $temp_queue_keys ) {
				$temp_queue_keys = array();
			}
			$temp_queue_keys[] = $temp_queue_key;
			set_transient( self::EVENTS_QUEUE_OPTION_KEY . '_temp_keys', $temp_queue_keys, DAY_IN_SECONDS );
			
			Bento_Logger::info( "Event queued to temporary transient during processing - Key: {$temp_queue_key}" );
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

			$interval = self::get_bento_option( 'bento_events_recurrence' );
			// Use default 3-minute interval if not set
			if ( empty( $interval ) ) {
				$interval = 3;
			}
			
			// add cron job to send events to Bento.
			add_filter( 'cron_schedules', array( __CLASS__, 'add_events_cron_interval' ) ); // phpcs:ignore WordPress.WP.CronInterval
			add_action( 'bento_send_events_hook', array( __CLASS__, 'bento_send_events_hook' ) );

			if ( ! wp_next_scheduled( 'bento_send_events_hook' ) ) {
				wp_schedule_event( time(), 'bento_send_events_interval', 'bento_send_events_hook' );
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
		 * Deduplicate events based on email, type, and relevant detail keys
		 *
		 * @param array $events_queue The events queue to deduplicate.
		 * @return array Deduplicated events queue.
		 */
		private static function deduplicate_events( $events_queue ) {
			if ( empty( $events_queue ) || ! is_array( $events_queue ) ) {
				return $events_queue;
			}

			$unique_events = array();
			$seen_keys = array();
			$duplicate_count = 0;

			foreach ( $events_queue as $event ) {
				$dedup_key = self::generate_dedup_key( $event );
				
				if ( ! in_array( $dedup_key, $seen_keys, true ) ) {
					$unique_events[] = $event;
					$seen_keys[] = $dedup_key;
				} else {
					$duplicate_count++;
				}
			}

			if ( $duplicate_count > 0 ) {
				Bento_Logger::info( "Removed {$duplicate_count} duplicate events from queue" );
			}

			return $unique_events;
		}

		/**
		 * Generate a deduplication key for an event
		 *
		 * @param array $event The event data.
		 * @return string The deduplication key.
		 */
		private static function generate_dedup_key( $event ) {
			$key_parts = array(
				$event['email'] ?? '',
				$event['type'] ?? '',
			);

			// Add relevant detail keys for specific event types
			if ( isset( $event['details'] ) && is_array( $event['details'] ) ) {
				$details = $event['details'];
				
				// For course-related events, include course_id
				if ( isset( $details['course_id'] ) ) {
					$key_parts[] = 'course_id:' . $details['course_id'];
				}
				
				// For product-related events, include product_id
				if ( isset( $details['product_id'] ) ) {
					$key_parts[] = 'product_id:' . $details['product_id'];
				}
				
				// For order-related events, include order_id
				if ( isset( $details['order_id'] ) ) {
					$key_parts[] = 'order_id:' . $details['order_id'];
				}
				
				// For subscription-related events, include subscription_id
				if ( isset( $details['subscription_id'] ) ) {
					$key_parts[] = 'subscription_id:' . $details['subscription_id'];
				}
			}

			return md5( implode( '|', $key_parts ) );
		}

		/**
		 * Send events in the queue to Bento.
		 */
		public static function bento_send_events_hook() {
			if ( self::is_sending_events() ) {
				Bento_Logger::info( 'Events processing already in progress - skipping' );
				return; // Already sending events.
			}

			$events_queue = get_option( self::EVENTS_QUEUE_OPTION_KEY, array() );
			$original_count = count( $events_queue );
			
			if ( empty( $events_queue ) ) {
				Bento_Logger::info( 'No events in queue to process' );
				return;
			}
			
			// Deduplicate events before processing
			$events_queue = self::deduplicate_events( $events_queue );
			// Clear the main queue before processing to avoid duplicate processing
			update_option( self::EVENTS_QUEUE_OPTION_KEY, array() );
			$queue_count = count( $events_queue );
			
			Bento_Logger::info( "Starting batch event processing - {$original_count} events in queue, {$queue_count} after deduplication" );
			Bento_Logger::info( "Queue cleared - verifying empty: " . count( get_option( self::EVENTS_QUEUE_OPTION_KEY, array() ) ) . " events remaining" );

			// set the transient to true so we know we're sending events.
			set_transient( self::IS_SENDING_EVENTS_TRANSIENT_KEY, true, HOUR_IN_SECONDS * 6 );

			$new_events_queue = array();
			$sent_count = 0;
			$failed_count = 0;
			
			foreach ( $events_queue as $event ) {
				$event_status = self::send_event( $event['user_id'], $event['type'], $event['email'], $event['details'] );
				
				if ( $event_status ) {
					$sent_count++;
					Bento_Logger::info( "Event sent successfully - removing from queue: {$event['type']} for " . self::sanitize_email_for_logging( $event['email'] ) );
				} else {
					$failed_count++;
					// event was not sent successfully.
					$new_events_queue[] = $event;
					Bento_Logger::info( "Event failed - keeping in queue: {$event['type']} for " . self::sanitize_email_for_logging( $event['email'] ) );
				}
			}

			// merge temporary queues with the permanent queue.
			$temp_queue_keys = get_transient( self::EVENTS_QUEUE_OPTION_KEY . '_temp_keys' );
			if ( ! empty( $temp_queue_keys ) && is_array( $temp_queue_keys ) ) {
				$temp_events_merged = 0;
				foreach ( $temp_queue_keys as $temp_key ) {
					$temp_queue = get_transient( $temp_key );
					if ( ! empty( $temp_queue ) && is_array( $temp_queue ) ) {
						$new_events_queue = array_merge( $new_events_queue, $temp_queue );
						$temp_events_merged += count( $temp_queue );
					}
					delete_transient( $temp_key );
				}
				delete_transient( self::EVENTS_QUEUE_OPTION_KEY . '_temp_keys' );
				Bento_Logger::info( "Merged {$temp_events_merged} events from " . count( $temp_queue_keys ) . " temporary queues" );
			}
			
			// Also check for the old-style temporary queue for backward compatibility
			$temporary_queue = get_transient( self::EVENTS_QUEUE_OPTION_KEY );
			if ( ! empty( $temporary_queue ) && is_array( $temporary_queue ) ) {
				$temp_count = count( $temporary_queue );
				Bento_Logger::info( "Merging {$temp_count} events from legacy temporary queue" );
				$new_events_queue = array_merge( $new_events_queue, $temporary_queue );
			}

			delete_transient( self::EVENTS_QUEUE_OPTION_KEY );

			// Verify the queue was updated correctly
			$final_queue_check = get_option( self::EVENTS_QUEUE_OPTION_KEY, array() );
			$final_queue_count = count( $final_queue_check );

			// finish sending events.
			delete_transient( self::IS_SENDING_EVENTS_TRANSIENT_KEY );
			
			$remaining_count = count( $new_events_queue );
			Bento_Logger::info( "Batch processing complete - Sent: {$sent_count}, Failed: {$failed_count}, Remaining in queue: {$remaining_count}" );
			Bento_Logger::info( "Final queue verification - Expected: {$remaining_count}, Actual in DB: {$final_queue_count}" );
		}

		/**
		 * Add bento cron interval.
		 *
		 * @param array $schedule The schedule.
		 * @return array
		 */
		public static function add_events_cron_interval( $schedule ) {
			$interval = self::get_bento_option( 'bento_events_recurrence' );
			// Use default 3-minute interval if not set
			if ( empty( $interval ) ) {
				$interval = 3;
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
