<?php
/**
 * Bento Elementor Form Handler
 *
 * @package Bento
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Bento_Elementor_Form_Handler {

    /**
     * Initialize the handler.
     */
    public static function init() {
        if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
            return;
        }
        
        add_action( 'elementor_pro/forms/actions/register', array( self::class, 'register_form_action' ) );
    }

    /**
     * Register Bento form action for Elementor Pro.
     *
     * @param \ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar $form_actions_registrar The registrar.
     */
    public static function register_form_action( $form_actions_registrar ) {
        require_once( plugin_dir_path( __FILE__ ) . '../form-actions/bento.php' );
        $form_actions_registrar->register( new Bento_Action_After_Submit() );
    }
}
