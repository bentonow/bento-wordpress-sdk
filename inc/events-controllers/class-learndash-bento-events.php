<?php
/**
 * LearnDash Bento Events Controller
 *
 * @package BentoHelper
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'LEARNDASH_VERSION' ) && ! class_exists( 'LearnDash_Bento_Events', false ) ) {
	/**
	 * LearnDash Bento Events
	 */
	class LearnDash_Bento_Events extends Bento_Events_Controller {
		/**
		 * Init class
		 *
		 *  @return void
		 */
		public function __construct() {
			add_action( 'learndash_course_completed', array( $this, 'learndash_course_completed_event' ) );
		}

		/**
		 * Send a LearnDash course completed event.
		 *
		 * @param array $course_data The course data.
		 * @return void
		 */
		public function learndash_course_completed_event( $course_data ) {
			$this->enqueue_event(
				$course_data['user']->ID,
				'learndash_course_completed',
				$course_data['user']->user_email,
				array(
					'course_id'        => $course_data['course']->ID,
					'course_name'      => $course_data['course']->post_title,
					'course_completed' => $course_data['course_completed'],
				)
			);
		}
	}
	new LearnDash_Bento_Events();
}
