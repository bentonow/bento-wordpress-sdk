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
			add_action(
				'learndash_course_completed',
				function( $ld_data ) {
					self::enqueue_event(
						$ld_data['user']->ID,
						'learndash_course_completed',
						$ld_data['user']->user_email,
						array(
							'course_id'        => $ld_data['course']->ID,
							'course_name'      => $ld_data['course']->post_title,
							'course_completed' => $ld_data['course_completed'],
						)
					);
				}
			);

			add_action(
				'learndash_lesson_completed',
				function( $ld_data ) {
					self::enqueue_event(
						$ld_data['user']->ID,
						'learndash_lesson_completed',
						$ld_data['user']->user_email,
						array(
							'lesson_id'       => $ld_data['lesson']->ID,
							'lesson_name'     => $ld_data['lesson']->post_title,
							'course_id'       => $ld_data['course']->ID,
							'course_name'     => $ld_data['course']->post_title,
							'course_progress' => $ld_data['progress'],
						)
					);
				}
			);

			add_action(
				'learndash_topic_completed',
				function( $ld_data ) {
					self::enqueue_event(
						$ld_data['user']->ID,
						'learndash_topic_completed',
						$ld_data['user']->user_email,
						array(
							'topic_id'        => $ld_data['topic']->ID,
							'topic_name'      => $ld_data['topic']->post_title,
							'lesson_id'       => $ld_data['lesson']->ID,
							'lesson_name'     => $ld_data['lesson']->post_title,
							'course_id'       => $ld_data['course']->ID,
							'course_name'     => $ld_data['course']->post_title,
							'course_progress' => $ld_data['progress'],
						)
					);
				}
			);

			add_action(
				'learndash_quiz_completed',
				function( $quizdata, $wp_user ) {
					self::enqueue_event(
						$wp_user->ID,
						'learndash_quiz_completed',
						$wp_user->user_email,
						array(
							'quiz_id'             => $quizdata['quiz'],
							'quiz_name'           => get_the_title( $quizdata['quiz'] ),
							'score'               => $quizdata['score'],
							'count'               => $quizdata['count'],
							'question_show_count' => $quizdata['question_show_count'],
							'pass'                => $quizdata['pass'],
							'points'              => $quizdata['points'],
							'total_points'        => $quizdata['total_points'],
							'percentage'          => $quizdata['percentage'],
							'timespent'           => $quizdata['timespent'],
							'has_graded'          => $quizdata['has_graded'],
							'course_id'           => ! empty( $quizdata['course'] ) ? $quizdata['course']->ID : 0,
							'course_name'         => ! empty( $quizdata['course'] ) ? $quizdata['course']->post_title : '',
							'lesson_id'           => ! empty( $quizdata['lesson'] ) ? $quizdata['lesson']->ID : 0,
							'lesson_name'         => ! empty( $quizdata['lesson'] ) ? $quizdata['lesson']->post_title : '',
							'topic_id'            => ! empty( $quizdata['topic'] ) ? $quizdata['topic']->ID : 0,
							'topic_name'          => ! empty( $quizdata['topic'] ) ? $quizdata['topic']->post_title : '',
						)
					);
				},
				10,
				2
			);
		}
	}
	new LearnDash_Bento_Events();
}
