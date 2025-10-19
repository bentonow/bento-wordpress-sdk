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
		const BENTO_COURSE_ENROLLMENT_SENT_META_KEY        = 'bento_ld_course_enrollment_sent';
		/**
		 * Init class
		 *
		 *  @return void
		 */
		public function __construct() {

			add_action(
				'learndash_course_completed',
				function( $ld_data ) {
					try {
						self::send_event(
							$ld_data['user']->ID,
							'learndash_course_completed',
							$ld_data['user']->user_email,
							array(
								'course_id'        => $ld_data['course']->ID,
								'course_name'      => $ld_data['course']->post_title,
								'course_completed' => $ld_data['course_completed'],
							)
						);
					} catch (Exception $e) {
						error_log('Bento LearnDash: Failed to send course completion event - ' . $e->getMessage());
					}
				}
			);

			add_action(
				'learndash_lesson_completed',
				function( $ld_data ) {
					try {
						self::send_event(
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
					} catch (Exception $e) {
						error_log('Bento LearnDash: Failed to send lesson completion event - ' . $e->getMessage());
					}
				}
			);

			add_action(
				'learndash_topic_completed',
				function( $ld_data ) {
					try {
						self::send_event(
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
					} catch (Exception $e) {
						error_log('Bento LearnDash: Failed to send topic completion event - ' . $e->getMessage());
					}
				}
			);

			add_action(
				'learndash_quiz_completed',
				function( $quizdata, $wp_user ) {
					try {
						self::send_event(
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
								'course_id'           => ! empty( $quizdata['course'] ) ? $quizdata['course']->ID ?? 0 : 0,
								'course_name'         => ! empty( $quizdata['course'] ) ? $quizdata['course']->post_title ?? '' : '',
								'lesson_id'           => ! empty( $quizdata['lesson'] ) ? $quizdata['lesson']->ID ?? 0 : 0,
								'lesson_name'         => ! empty( $quizdata['lesson'] ) ? $quizdata['lesson']->post_title ?? '' : '',
								'topic_id'            => ! empty( $quizdata['topic'] ) ? $quizdata['topic']->ID ?? 0 : 0,
								'topic_name'          => ! empty( $quizdata['topic'] ) ? $quizdata['topic']->post_title ?? '' : '',
							)
						);
					} catch (Exception $e) {
						error_log('Bento LearnDash: Failed to send quiz completion event - ' . $e->getMessage());
					}
				},
				10,
				2
			);

			// user earned a new certificate.
			add_action(
				'learndash_course_completed',
				function( $ld_data ) {
					$certificate_link = learndash_get_course_certificate_link( $ld_data['course']->ID, $ld_data['user']->ID );
					if ( ! empty( $certificate_link ) ) {
						$this->send_user_earned_new_certificate_event(
							$ld_data['user']->ID,
							$ld_data['user']->user_email,
							array(
								'course_id'        => $ld_data['course']->ID,
								'course_name'      => $ld_data['course']->post_title,
								'certificate_link' => $certificate_link,
							)
						);
					}
				}
			);
			add_action(
				'learndash_quiz_completed',
				function( $quizdata, $wp_user ) {
					$certificate_link = learndash_get_certificate_link( $quizdata['quiz'], $wp_user->ID );
					if ( ! empty( $certificate_link ) ) {
						$this->send_user_earned_new_certificate_event(
							$wp_user->ID,
							$wp_user->user_email,
							array(
								'course_id'        => ! empty( $quizdata['course'] ) ? $quizdata['course']->ID : 0,
								'course_name'      => ! empty( $quizdata['course'] ) ? $quizdata['course']->post_title : '',
								'quiz_id'          => $quizdata['quiz'],
								'quiz_name'        => get_the_title( $quizdata['quiz'] ),
								'certificate_link' => $certificate_link,
							)
						);
					}
				},
				10,
				2
			);

			// user essay has been graded.
			add_action(
				'learndash_essay_all_quiz_data_updated',
				function( $quiz_id, $question_id, $updated_scoring_data, $essay_post ) {
					if ( 'graded' !== $essay_post->post_status || intval( $updated_scoring_data['score_difference'] ) <= 0 ) {
						return; // essay is not graded or event already sent.
					}
					try {
						$user_email = self::resolve_user_email( $essay_post->post_author, 'essay grading event' );

						if ( ! $user_email ) {
							return;
						}

						self::send_event(
							$essay_post->post_author,
							'learndash_essay_graded',
							$user_email,
							array(
								'quiz_id'                   => $quiz_id,
								'quiz_name'                 => get_the_title( $quiz_id ),
								'question_id'               => $question_id,
								'updated_question_score'    => $updated_scoring_data['updated_question_score'],
								'points_awarded_difference' => $updated_scoring_data['points_awarded_difference'],
							)
						);
					} catch (Exception $e) {
						error_log('Bento LearnDash: Failed to send essay grading event - ' . $e->getMessage());
					}
				},
				10,
				4
			);

			// users assignment has been graded.
			add_action(
				'learndash_assignment_approved',
				function( $assignment_id ) {
					$assignment = get_post( $assignment_id );
					$course_id  = get_post_meta( $assignment_id, 'course_id', true );

					try {
						$user_email = self::resolve_user_email( $assignment->post_author, 'assignment approval event' );

						if ( ! $user_email ) {
							return;
						}

						self::send_event(
							$assignment->post_author,
							'learndash_assignment_approved',
							$user_email,
							array(
								'assignment_id'   => $assignment_id,
								'assignment_name' => $assignment->post_title,
								'course_id'       => $course_id,
								'course_name'     => get_the_title( $course_id ),
							)
						);
					} catch (Exception $e) {
						error_log('Bento LearnDash: Failed to send assignment approval event - ' . $e->getMessage());
					}
				}
			);

			// users assignment has a new comment.
			add_action(
				'comment_post',
				function( $comment_id, $comment_approved, $commentdata ) {
					$post_type = get_post_type( $commentdata['comment_post_ID'] );
					if ( learndash_get_post_type_slug( 'assignment' ) === $post_type ) {
						$course_id = get_post_meta( $commentdata['comment_post_ID'], 'course_id', true );
						try {
							$user_email = self::resolve_user_email( $commentdata['user_id'], 'assignment comment event' );

							if ( ! $user_email ) {
								return;
							}

							self::send_event(
								$commentdata['user_id'],
								'learndash_assignment_new_comment',
								$user_email,
								array(
									'assignment_id'        => $commentdata['comment_post_ID'],
									'assignment_name'      => get_the_title( $commentdata['comment_post_ID'] ),
									'course_id'            => $course_id,
									'course_name'          => get_the_title( $course_id ),
									'comment_id'           => $comment_id,
									'comment_author'       => $commentdata['comment_author'],
									'comment_content'      => $commentdata['comment_content'],
									'comment_author_email' => $commentdata['comment_author_email'],
									'comment_approved'     => $comment_approved,
								)
							);
						} catch (Exception $e) {
							error_log('Bento LearnDash: Failed to send assignment comment event - ' . $e->getMessage());
						}
					}
				},
				10,
				3
			);

			// user enrolls in a course.
			add_action(
				'learndash_update_course_access',
				function( $user_id, $course_id, $course_access_list, $remove ) {
					if ( ! $remove ) {
						try {
							// Check if enrollment event was already sent for this user-course combination
							$enrollment_events_sent = get_user_meta( $user_id, self::BENTO_COURSE_ENROLLMENT_SENT_META_KEY, true );
							if ( ! is_array( $enrollment_events_sent ) ) {
								$enrollment_events_sent = array();
							}

							// Only send event if not already sent for this course (using isset for O(1) lookup)
							if ( ! isset( $enrollment_events_sent[ $course_id ] ) ) {
								$user_data = get_userdata( $user_id );
								if ( ! $user_data ) {
									throw new Exception( "User data not found for user ID: {$user_id}" );
								}

								self::send_event(
									$user_id,
									'learndash_user_enrolled_in_course',
									$user_data->user_email,
									array(
										'course_id'   => $course_id,
										'course_name' => get_the_title( $course_id ),
									)
								);

								// Mark this course enrollment as sent (store course_id as key for fast lookup)
								$enrollment_events_sent[ $course_id ] = time();
								update_user_meta( $user_id, self::BENTO_COURSE_ENROLLMENT_SENT_META_KEY, $enrollment_events_sent );
							}
						} catch ( Exception $e ) {
							// Log the error but don't break the site
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( '[Bento LearnDash Events] Error processing course enrollment: ' . $e->getMessage() );
							}
							// Optionally use Bento_Logger if available
							if ( class_exists( 'Bento_Logger' ) ) {
								Bento_Logger::log( '[Bento LearnDash Events] Error processing course enrollment: ' . $e->getMessage() );
		}
	}

	/**
	 * Safely resolve a user's email address for an event context.
	 */
	protected static function resolve_user_email( $user_id, $context ) {
		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_email ) ) {
			$message = sprintf(
				'Bento LearnDash: Unable to resolve user email for %s (user ID: %s).',
				$context,
				$user_id
			);

			if ( class_exists( 'Bento_Logger' ) ) {
				Bento_Logger::log( $message );
			} else {
				error_log( $message );
			}

			return null;
		}

		return $user->user_email;
	}
}
				},
				10,
				4
			);

			// user enrolls in a group.
			add_action(
				'ld_added_group_access',
				function( $user_id, $group_id ) {
					try {
						$user_email = self::resolve_user_email( $user_id, 'group enrollment event' );

						if ( ! $user_email ) {
							return;
						}

						self::send_event(
							$user_id,
							'learndash_user_enrolled_in_group',
							$user_email,
							array(
								'group_id'   => $group_id,
								'group_name' => get_the_title( $group_id ),
							)
						);
					} catch (Exception $e) {
						error_log('Bento LearnDash: Failed to send group enrollment event - ' . $e->getMessage());
					}
				},
				10,
				2
			);

			// users buys a course/group.
			add_action(
				'learndash_transaction_created',
				function( $transaction_id ) {
					if ( is_null( get_post( $transaction_id ) ) ) {
						return;
					}

					$post_id = get_post_meta( $transaction_id, 'post_id', true );
					$user_id = get_post_meta( $transaction_id, 'user_id', true );
					if ( is_null( $post_id ) || is_null( $user_id ) ) {
						return;
					}

					$post_type = get_post_type( $post_id );
					if ( learndash_get_post_type_slug( 'course' ) === $post_type ) {
						try {
							$user_email = self::resolve_user_email( $user_id, 'course purchase event' );

							if ( ! $user_email ) {
								return;
							}

							self::send_event(
								$user_id,
								'learndash_user_purchased_course',
								$user_email,
								array(
									'course_id'   => $post_id,
									'course_name' => get_the_title( $post_id ),
								)
							);
						} catch (Exception $e) {
							error_log('Bento LearnDash: Failed to send course purchase event - ' . $e->getMessage());
						}
					} elseif ( learndash_get_post_type_slug( 'group' ) === $post_type ) {
						try {
							$user_email = self::resolve_user_email( $user_id, 'group purchase event' );

							if ( ! $user_email ) {
								return;
							}

							self::send_event(
								$user_id,
								'learndash_user_purchased_group',
								$user_email,
								array(
									'group_id'   => $post_id,
									'group_name' => get_the_title( $post_id ),
								)
							);
						} catch (Exception $e) {
							error_log('Bento LearnDash: Failed to send group purchase event - ' . $e->getMessage());
						}
					}

				}
			);

		}

		/**
		 * Send the user earned a new certificate event.
		 *
		 * @param int    $user_id User ID.
		 * @param string $user_email User email.
		 * @param array  $event_details Event details.
		 */
		private function send_user_earned_new_certificate_event( $user_id, $user_email, $event_details ) {
			try {
				self::send_event(
					$user_id,
					'learndash_user_earned_new_certificate',
					$user_email,
					$event_details
				);
			} catch (Exception $e) {
				error_log('Bento LearnDash: Failed to send certificate event - ' . $e->getMessage());
			}
		}




	}
	new LearnDash_Bento_Events();
}
