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
		const BENTO_LAST_NOT_COMPLETED_EVENT_SENT_META_KEY = 'bento_ld_last_not_completed_event_sent';
		const BENTO_DRIP_CONTENT_META_KEY                  = 'bento_ld_drip_content';
		/**
		 * Init class
		 *
		 *  @return void
		 */
		public function __construct() {
			// add cron job to send scheduled events.
			add_action( 'bento_learndash_scheduled_events_hook', array( __CLASS__, 'bento_learndash_scheduled_events_hook' ) );
			if ( ! wp_next_scheduled( 'bento_learndash_scheduled_events_hook' ) ) {
				wp_schedule_event( time(), 'hourly', 'bento_learndash_scheduled_events_hook' );
			}

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
							'course_id'           => ! empty( $quizdata['course'] ) ? $quizdata['course']->ID ?? 0 : 0,
							'course_name'         => ! empty( $quizdata['course'] ) ? $quizdata['course']->post_title ?? '' : '',
							'lesson_id'           => ! empty( $quizdata['lesson'] ) ? $quizdata['lesson']->ID ?? 0 : 0,
							'lesson_name'         => ! empty( $quizdata['lesson'] ) ? $quizdata['lesson']->post_title ?? '' : '',
							'topic_id'            => ! empty( $quizdata['topic'] ) ? $quizdata['topic']->ID ?? 0 : 0,
							'topic_name'          => ! empty( $quizdata['topic'] ) ? $quizdata['topic']->post_title ?? '' : '',
						)
					);
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
					self::enqueue_event(
						$essay_post->post_author,
						'learndash_essay_graded',
						get_userdata( $essay_post->post_author )->user_email,
						array(
							'quiz_id'                   => $quiz_id,
							'quiz_name'                 => get_the_title( $quiz_id ),
							'question_id'               => $question_id,
							'updated_question_score'    => $updated_scoring_data['updated_question_score'],
							'points_awarded_difference' => $updated_scoring_data['points_awarded_difference'],
						)
					);
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
					self::enqueue_event(
						$assignment->post_author,
						'learndash_assignment_approved',
						get_userdata( $assignment->post_author )->user_email,
						array(
							'assignment_id'   => $assignment_id,
							'assignment_name' => $assignment->post_title,
							'course_id'       => $course_id,
							'course_name'     => get_the_title( $course_id ),
						)
					);
				}
			);

			// users assignment has a new comment.
			add_action(
				'comment_post',
				function( $comment_id, $comment_approved, $commentdata ) {
					$post_type = get_post_type( $commentdata['comment_post_ID'] );
					if ( learndash_get_post_type_slug( 'assignment' ) === $post_type ) {
						$course_id = get_post_meta( $commentdata['comment_post_ID'], 'course_id', true );
						self::enqueue_event(
							$commentdata['user_id'],
							'learndash_assignment_new_comment',
							get_userdata( $commentdata['user_id'] )->user_email,
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
						self::enqueue_event(
							$user_id,
							'learndash_user_enrolled_in_course',
							get_userdata( $user_id )->user_email,
							array(
								'course_id'   => $course_id,
								'course_name' => get_the_title( $course_id ),
							)
						);
					}
				},
				10,
				4
			);

			// user enrolls in a group.
			add_action(
				'ld_added_group_access',
				function( $user_id, $group_id ) {
					self::enqueue_event(
						$user_id,
						'learndash_user_enrolled_in_group',
						get_userdata( $user_id )->user_email,
						array(
							'group_id'   => $group_id,
							'group_name' => get_the_title( $group_id ),
						)
					);
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
						self::enqueue_event(
							$user_id,
							'learndash_user_purchased_course',
							get_userdata( $user_id )->user_email,
							array(
								'course_id'   => $post_id,
								'course_name' => get_the_title( $post_id ),
							)
						);
					} elseif ( learndash_get_post_type_slug( 'group' ) === $post_type ) {
						self::enqueue_event(
							$user_id,
							'learndash_user_purchased_group',
							get_userdata( $user_id )->user_email,
							array(
								'group_id'   => $post_id,
								'group_name' => get_the_title( $post_id ),
							)
						);
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
			self::enqueue_event(
				$user_id,
				'learndash_user_earned_new_certificate',
				$user_email,
				$event_details
			);
		}

		/**
		 * Verify last login date and send event.
		 */
		public static function bento_learndash_scheduled_events_hook() {
			WP_DEBUG && error_log( '[Bento] - LearnDash scheduled events started.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			// self::user_not_completed_events_handler();
			self::user_drip_content_events_handler();

			WP_DEBUG && error_log( '[Bento] - LearnDash scheduled events finished.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		/**
		 * Handler for LD drip content events.
		 *
		 *  @return void
		 */
		private static function user_drip_content_events_handler() {
			$current_drip_content = get_option( self::BENTO_DRIP_CONTENT_META_KEY, array() );

			// get all ld courses.
			$ld_courses = get_posts(
				array(
					'post_type'      => learndash_get_post_type_slug( 'course' ),
					'posts_per_page' => -1,
				)
			);
			foreach ( $ld_courses as $course ) {
				// get all user enrolled in the course.
				$course_user_query = learndash_get_users_for_course( $course->ID );
				if ( ! $course_user_query instanceof WP_User_Query ) {
					continue;
				}
				$enrolled_user_ids = $course_user_query->get_results();

				// check the drip_content status for each user.
				foreach ( $enrolled_user_ids as $user_id ) {
					// lessons.
					$lessons = learndash_course_get_lessons(
						$course->ID,
						array(
							'return_type' => 'WP_Post',
							'per_page'    => 0,
						)
					);
					if ( ( is_array( $lessons ) ) && ( ! empty( $lessons ) ) ) {
						foreach ( $lessons as $lesson ) {
							$ld_lesson_access_from = ld_lesson_access_from( $lesson->ID, $user_id, $course->ID );

							// now it's open, so we need to check if we need to send drip content event.
							if ( empty( $ld_lesson_access_from ) ) {
								if ( isset( $current_drip_content[ $user_id ][ $lesson->ID ] ) ) {
									// send drip content event.
									self::enqueue_event(
										$user_id,
										'learndash_drip_content',
										get_userdata( $user_id )->user_email,
										array(
											'lesson_id'   => $lesson->ID,
											'lesson_name' => $lesson->post_title,
											'course_id'   => $course->ID,
											'course_name' => $course->post_title,
										)
									);
									unset( $current_drip_content[ $user_id ][ $lesson->ID ] );
								}
							} else {
								// set the content as not available.
								$current_drip_content[ $user_id ][ $lesson->ID ] = true;
							}
						}
					}
				}
			}

			// update the drip content meta.
			update_option( self::BENTO_DRIP_CONTENT_META_KEY, $current_drip_content );

			WP_DEBUG && error_log( '[Bento] - User drip content events checked.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		/**
		 * Handler for not completed LD events.
		 *
		 *  @return void
		 */
		private static function user_not_completed_events_handler() {
			$bento_user_not_completed_events_interval = self::get_bento_option( 'bento_events_user_not_completed_content' );
			$bento_repeat_not_events                  = self::get_bento_option( 'bento_events_repeat_not_event' );

			// useful caches.
			$users_should_send_events = array();

			if ( empty( $bento_user_not_completed_events_interval ) ) {
				WP_DEBUG && error_log( '[Bento] - User not completed content events disabled.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			// get all ld courses.
			$ld_courses = get_posts(
				array(
					'post_type'      => learndash_get_post_type_slug( 'course' ),
					'posts_per_page' => -1,
				)
			);
			foreach ( $ld_courses as $course ) {
				// get all user enrolled in the course.
				$course_user_query = learndash_get_users_for_course( $course->ID );
				if ( ! $course_user_query instanceof WP_User_Query ) {
					continue;
				}
				$enrolled_user_ids = $course_user_query->get_results();

				// check the completed status for the course for each user.
				foreach ( $enrolled_user_ids as $user_id ) {
					$course_completed = learndash_course_completed( $user_id, $course->ID );
					if ( $course_completed ) {
						continue;
					}

					if ( ! isset( $users_should_send_events[ $user_id ] ) ) {
						$users_should_send_events[ $user_id ] = self::should_send_not_completed_event( $user_id, $bento_repeat_not_events );
					}

					if ( $users_should_send_events[ $user_id ] ) {
						self::enqueue_event(
							$user_id,
							'learndash_course_not_completed',
							get_userdata( $user_id )->user_email,
							array(
								'course_id'   => $course->ID,
								'course_name' => $course->post_title,
							)
						);
						// set last event sent.
						update_user_meta( $user_id, self::BENTO_LAST_NOT_COMPLETED_EVENT_SENT_META_KEY, time() );
					}

					// lessons.
					$lessons = learndash_course_get_lessons(
						$course->ID,
						array(
							'return_type' => 'WP_Post',
							'per_page'    => 0,
						)
					);
					if ( ( is_array( $lessons ) ) && ( ! empty( $lessons ) ) ) {
						foreach ( $lessons as $lesson ) {
							$lesson_completed = learndash_is_lesson_complete( $user_id, $lesson->ID, $course->ID );
							if ( $lesson_completed ) {
								continue;
							}

							if ( $users_should_send_events[ $user_id ] ) {
								self::enqueue_event(
									$user_id,
									'learndash_lesson_not_completed',
									get_userdata( $user_id )->user_email,
									array(
										'lesson_id'   => $lesson->ID,
										'lesson_name' => $lesson->post_title,
										'course_id'   => $course->ID,
										'course_name' => $course->post_title,
									)
								);
								// set last event sent.
								update_user_meta( $user_id, self::BENTO_LAST_NOT_COMPLETED_EVENT_SENT_META_KEY, time() );
							}

							// topics.
							$topics = learndash_course_get_topics(
								$course->ID,
								$lesson->ID,
								array(
									'return_type' => 'WP_Post',
									'per_page'    => 0,
								)
							);
							if ( ( is_array( $topics ) ) && ( ! empty( $topics ) ) ) {
								foreach ( $topics as $topic ) {
									$topic_completed = learndash_is_topic_complete( $user_id, $topic->ID, $course->ID );
									if ( $topic_completed ) {
												continue;
									}

									if ( $users_should_send_events[ $user_id ] ) {
										self::enqueue_event(
											$user_id,
											'learndash_topic_not_completed',
											get_userdata( $user_id )->user_email,
											array(
												'topic_id' => $topic->ID,
												'topic_name' => $topic->post_title,
												'lesson_id' => $lesson->ID,
												'lesson_name' => $lesson->post_title,
												'course_id' => $course->ID,
												'course_name' => $course->post_title,
											)
										);
										// set last event sent.
										update_user_meta( $user_id, self::BENTO_LAST_NOT_COMPLETED_EVENT_SENT_META_KEY, time() );
									}

									// Get Topic's Quizzes.
									$topic_quizzes = learndash_course_get_quizzes(
										$course->ID,
										$topic->ID,
										array(
											'return_type' => 'WP_Post',
											'per_page'    => 0,
										)
									);
									if ( ( is_array( $topic_quizzes ) ) && ( ! empty( $topic_quizzes ) ) ) {
										foreach ( $topic_quizzes as $topic_quiz ) {
											$topic_quiz_completed = learndash_is_quiz_complete( $user_id, $topic_quiz->ID, $course->ID );
											if ( $topic_quiz_completed ) {
																continue;
											}

											if ( $users_should_send_events[ $user_id ] ) {
													self::enqueue_event(
														$user_id,
														'learndash_quiz_not_completed',
														get_userdata( $user_id )->user_email,
														array(
															'quiz_id'  => $topic_quiz->ID,
															'quiz_name' => $topic_quiz->post_title,
															'topic_id' => $topic->ID,
															'topic_name' => $topic->post_title,
															'lesson_id' => $lesson->ID,
															'lesson_name' => $lesson->post_title,
															'course_id' => $course->ID,
															'course_name' => $course->post_title,
														)
													);
											} // end topic quizzes.
										}
									} // end topics.

									// Get lesson's quizzes.
									$lesson_quizzes = learndash_course_get_quizzes(
										$course->ID,
										$lesson->ID,
										array(
											'return_type' => 'WP_Post',
											'per_page'    => 0,
										)
									);
									if ( ( is_array( $lesson_quizzes ) ) && ( ! empty( $lesson_quizzes ) ) ) {
										foreach ( $lesson_quizzes as $lesson_quiz ) {
											$lesson_quiz_completed = learndash_is_quiz_complete( $user_id, $lesson_quiz->ID, $course->ID );
											if ( $lesson_quiz_completed ) {
																continue;
											}

											if ( $users_should_send_events[ $user_id ] ) {
													self::enqueue_event(
														$user_id,
														'learndash_quiz_not_completed',
														get_userdata( $user_id )->user_email,
														array(
															'quiz_id' => $lesson_quiz->ID,
															'quiz_name' => $lesson_quiz->post_title,
															'lesson_id' => $lesson->ID,
															'lesson_name' => $lesson->post_title,
															'course_id' => $course->ID,
															'course_name' => $course->post_title,
														)
													);
											} // end lesson quizzes.
										}
									} // end lesson quizzes.
								}
							} // end lessons.

							// Get a list of course (global) quizzes.
							$global_quizzes = learndash_course_get_quizzes(
								$course->ID,
								$course->ID,
								array(
									'return_type' => 'WP_Post',
									'per_page'    => 0,
								)
							);
							if ( ( is_array( $global_quizzes ) ) && ( ! empty( $global_quizzes ) ) ) {
								foreach ( $global_quizzes as $global_quiz ) {
									$global_quiz_completed = learndash_is_quiz_complete( $user_id, $global_quiz->ID, $course->ID );
									if ( $global_quiz_completed ) {
												continue;
									}

									if ( $users_should_send_events[ $user_id ] ) {
										self::enqueue_event(
											$user_id,
											'learndash_quiz_not_completed',
											get_userdata( $user_id )->user_email,
											array(
												'quiz_id' => $global_quiz->ID,
												'quiz_name' => $global_quiz->post_title,
												'course_id' => $course->ID,
												'course_name' => $course->post_title,
											)
										);
									} // end course quizzes.
								}
							} // end global quizzes.
						}
					}
				}
			}

			WP_DEBUG && error_log( '[Bento] - User not completed content events checked.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		/**
		 * Check when send not completed events for a user.
		 *
		 * @param int $user_id User ID.
		 * @param int $bento_repeat_not_events Repeat not completed events interval.
		 * @return boolean True if should send event, false otherwise.
		 */
		private static function should_send_not_completed_event( $user_id, $bento_repeat_not_events ) {
			$last_event_sent = get_user_meta( $user_id, self::BENTO_LAST_NOT_COMPLETED_EVENT_SENT_META_KEY, true );
			if ( ! empty( $last_event_sent ) && ( empty( $bento_repeat_not_events ) || $last_event_sent > strtotime( "-$bento_repeat_not_events day" ) ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Unschedule the LearnDash Bento events cron job. Used when the plugin is deactivated.
		 */
		public static function remove_cron_jobs() {
			$send_events_timestamp = wp_next_scheduled( 'bento_learndash_scheduled_events_hook' );
			wp_unschedule_event( $send_events_timestamp, 'bento_learndash_scheduled_events_hook' );
		}
	}
	new LearnDash_Bento_Events();
}
