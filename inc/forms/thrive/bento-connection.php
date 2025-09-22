<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_Bento extends Thrive_Dash_List_Connection_Abstract {
	/**
	 * Return the connection type
	 *
	 * @return String
	 */
	public static function get_type() {
		return 'autoresponder';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return 'Bento';
	}

	/**
	 * @return bool
	 */
	public function has_tags() {

		return false;
	}

	/**
	 * @return bool
	 */
	public function has_custom_fields() {
		return false;
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'Bento' );
	}

	/**
	 * @return mixed|Thrive_Dash_List_Connection_Abstract
	 */
	public function read_credentials() {

		$connection = $this->post( 'connection', array() );

		if ( empty( $connection['api_url'] ) || empty( $connection['api_key'] ) || empty( $connection['hash_key'] ) ) {
			$connection['api_key'] = 'plugin_activated';
			$connection['hash_key'] = 'plugin_activated';
		}

		$this->set_credentials( $connection );
		
		$this->save();

		return $this->success( __( 'Bento connected successfully', 'thrive-dash' ) );
	}

	/**
	 * @return bool
	 */
	public function test_connection() {
		// This is always true because we use Bento's plugin (with keys) to connect to Bento! Yay, simplicity!
		return true;
	}

	/**
	 * @return mixed|Thrive_Dash_Api_Bento
	 * @throws Thrive_Dash_Api_Bento_Exception
	 */
	protected function get_api_instance() {
		// And as a result of using the plugin, we don't need to instantiate the API ourselves!
		return null;
	}

	/**
	 * @return array|bool
	 */
	protected function _get_lists() {
		return array(
			array(
				'id'   => 'main_list',
				'name' => 'Find or Create User'
			)
		);
	}

	/**
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return mixed|string
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		if ( empty( $arguments['email'] ) || ! is_email( $arguments['email'] ) ) {
			return false;
		}

		$name_array = array();
		
		if ( ! empty( $arguments['name'] ) ) {
			list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );
			$name_array = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'full_name'  => $arguments['name'],
			);
		}
		
		$args = array(
			'email'   => $arguments['email'],
		);

		$args = array_merge( $args, $name_array );
		
		# Event Name is the form name
		$event_name = '$thrive.optin.' . $arguments['form_identifier'];
		
		return Bento_Events_Controller::trigger_event(
			get_current_user_id(),
			$event_name,
			$arguments['email'],
			null,
			$args
		);
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return 'VAR_EMAIL';
	}

	/**
	 * output directly the html for a connection form from views/setup
	 *
	 * @param string $filename
	 * @param array $data allows passing variables to the view file
	 */
	public function output_controls_html( $filename, $data = [] ) {
		include dirname( dirname( __DIR__ ) ) . '/forms/thrive/bento-view.php';
	}

	public function get_logo_url() {
		return 'https://app.bentonow.com/characters/backdrop.png';
	}

	
}
