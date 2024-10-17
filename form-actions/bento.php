<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Elementor form Bento action.
 *
 * Custom Elementor form action which adds new subscriber to Bento after form submission.
 *
 * @since 1.0.0
 */
class Bento_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Get action name.
	 *
	 * Retrieve Bento action name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'bento';
	}

	/**
	 * Get action label.
	 *
	 * Retrieve Bento action label.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Bento', 'elementor-forms-bento-action' );
	}

	/**
	 * Register action controls.
	 *
	 * Add input fields to allow the user to customize the action settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {

		$widget->start_controls_section(
			'section_bento',
			[
				'label' => esc_html__( 'Bento', 'elementor-forms-bento-action' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'bento_event',
			[
				'label' => esc_html__( 'Bento Event Name', 'elementor-forms-bento-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'description' => esc_html__( 'The name of the event you want to fire. You can use this to start an automation or Flow inside Bento.', 'elementor-forms-bento-action' ),
				'default' => '$subscribed.elementor',
			]
		);

		$widget->add_control(
			'bento_email_field',
			[
				'label' => esc_html__( 'Email Field ID', 'elementor-forms-bento-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'email',
				'description' => esc_html__( 'The ID of the field that contains the email address (edit the field and find it under "Advanced" tab). We very strongly recommend LOWERCASE and UNDERSCORED ID names.', 'elementor-forms-bento-action' ),
			]
		);

		$widget->add_control(
			'bento_map_all_fields',
			[
				'label' => esc_html__( 'Map all fields as custom fields?', 'elementor-forms-bento-action' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => '',
				'label_on' => esc_html__( 'Yes', 'elementor-forms-bento-action' ),
				'label_off' => esc_html__( 'No', 'elementor-forms-bento-action' ),
				'description' => esc_html__( 'This will map the all form fields as custom fields on the user sent to Bento. The ID will be used as the field name and the value will be used as the field value.', 'elementor-forms-bento-action' ),
			]
		);

		$widget->end_controls_section();

	}

	/**
	 * Run action.
	 *
	 * Runs the Bento action after form submission.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {

		$settings = $record->get( 'form_settings' );

		//  Make sure that there is a Bento installation URL.
		if ( empty( $settings['bento_event'] ) ) {
			return;
		}

		// Make sure that there is a Bento email field ID (required by Bento to subscribe users).
		if ( empty( $settings['bento_email_field'] ) ) {
			return;
		}

		// Get submitted form data.
		$raw_fields = $record->get( 'fields' );

		// Normalize form data.
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		// Make sure the user entered an email (required by Bento to subscribe users).
		if ( empty( $fields[ $settings['bento_email_field'] ] ) ) {
			return;
		}

		// Request data based on the param list at https://bento.co/api
		$bento_data = [
			'email' => $fields[ $settings['bento_email_field'] ],
			'ipaddress' => \ElementorPro\Core\Utils::get_client_ip(),
			'referrer' => isset( $_POST['referrer'] ) ? $_POST['referrer'] : '',
		];
		
		Bento_Events_Controller::trigger_event(
			get_current_user_id(),
			$settings['bento_event'],
			$fields[ $settings['bento_email_field'] ],
			$bento_data,
			$fields
		);

	}

	/**
	 * On export.
	 *
	 * Clears Bento form settings/fields when exporting.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $element
	 */
	public function on_export( $element ) {

		unset(
			$element['bento_url'],
			$element['bento_event'],
			$element['bento_email_field'],
			$element['bento_name_field']
		);

		return $element;

	}

}
