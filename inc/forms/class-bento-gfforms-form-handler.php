<?php
/**
 * Gravity Forms - Bento Events Controller
 *
 * @package BentoHelper
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'GFForms' ) && ! class_exists( 'Bento_GFForms_Form_Handler', false ) ) {
    /**
     * Gravity Forms Bento Events
     */
    class Bento_GFForms_Form_Handler extends Bento_Events_Controller {

        /**
         * Constructor.
         *
         * @return void
         */
        public static function init() {
            # Handles the form submission event
            add_action('gform_after_submission',
                function( $entry, $form ) {
                    $field_data_map = [];
                    $user_email = null;
                    $form_id = $form['id'];
                    $form_title = $form['title'] ?? null;
                    $event_name = '$GFormsSubmit';

                    if ( $form_title ) {
                        $event_name .= ':' . $form_title . '-' . $form_id;
                    } else {
                        $event_name .= ':' . $form_id;
                    }

                    foreach ( $form['fields'] as $field ) {
                        $field_data_map[$field['label']] = self::_rgar( $entry, $field['id'] );

                        # Let's take the first email field as the best guess for the user's email
                        if ( $field['type'] === 'email' && $user_email === null ) {
                            $user_email = self::_rgar( $entry, $field['id'] );
                        }
                    }

                    if ( empty( $user_email ) || ! is_email( $user_email ) ) {
                        return;
                    }

                    self::send_event(
                        null,
                        $event_name,
                        $user_email,
                        null,
                        $field_data_map
                    );
                },
                10,
                2
            );
        }

        /**
         * Get a specific property of an array without needing to check if that property exists.
         *
         * Provide a default value if you want to return a specific value if the property is not set.
         *
         * @since  Unknown
         * @access public
         *
         * @param array $array Array from which the property's value should be retrieved.
         * @param string $prop Name of the property to be retrieved.
         * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
         *
         * @return null|string|mixed The value
         */
        private static function _rgar( $array, $prop, $default = null ) {

            if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
                return $default;
            }

            if ( isset( $array[ $prop ] ) ) {
                $value = $array[ $prop ];
            } else {
                $value = '';
            }

            return empty( $value ) && $default !== null ? $default : $value;
        }
    }
}
