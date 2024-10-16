<?php
/**
 * Elementor - Bento Events Controller
 *
 * @package BentoHelper
 */

defined( 'ABSPATH' ) || exit;

if ( defined('ELEMENTOR_PRO_VERSION') ) {
    if ( ! class_exists( 'Elementor_Bento_Events', false ) ) {
        /**
         * Elementor Events
         */
        class Elementor_Bento_Events extends \ElementorPro\Modules\Forms\Classes\Action_Base {

            /**
             * Get action name.
             *
             * @return string
             */
            public function get_name() {
                return 'bento';
            }

            /**
             * Get action label.
             *
             * @return string
             */
            public function get_label() {
                return esc_html__( 'Bento', 'bentonow' );
            }

            /**
             * Register action controls.
             *
             * @param \Elementor\Widget_Base $widget
             */
            public function register_settings_section( $widget ) {
                $widget->start_controls_section(
                    'section_bento',
                    [
                        'label' => esc_html__( 'Bento', 'bentonow' ),
                        'condition' => [
                            'submit_actions' => $this->get_name(),
                        ],
                    ]
                );

                $widget->add_control(
                    'bento_event_name',
                    [
                        'label' => esc_html__( 'Event Name', 'bentonow' ),
                        'type' => \Elementor\Controls_Manager::TEXT,
                        'default' => 'form_submitted',
                        'placeholder' => esc_html__( 'Enter event name', 'bentonow' ),
                    ]
                );

                $widget->end_controls_section();
            }

            /**
             * Run action.
             *
             * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
             * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
             */
            public function run( $record, $ajax_handler ) {
                $settings = $record->get( 'form_settings' );
                $event_name = $settings['bento_event_name'] ?? 'form_submitted';

                $fields = $record->get( 'fields' );
                $event_details = [];
                foreach ( $fields as $key => $field ) {
                    $event_details[$key] = $field['value'];
                }

                // Send event to Bento
                Bento_Events_Controller::send_event(
                    get_current_user_id(),
                    $event_name,
                    wp_get_current_user()->user_email,
                    $event_details
                );
            }

            /**
             * On export.
             *
             * @param array $element
             * @return array
             */
            public function on_export( $element ) {
                unset( $element['bento_event_name'] );
                return $element;
            }
        }
    }

    // Register the Bento action with Elementor Pro
    add_action( 'elementor_pro/forms/actions/register', function( $form_actions_registrar ) {
        $form_actions_registrar->register( new Elementor_Bento_Events() );
    });
}