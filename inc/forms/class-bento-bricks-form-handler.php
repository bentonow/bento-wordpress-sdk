<?php
/**
 * Bento Bricks Form Handler
 *
 * @package Bento
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Bento_Bricks_Form_Handler {

    /**
     * Initialize the handler.
     */
    public static function init() {
        add_action( 'bricks/form/custom_action', array( self::class, 'handle_form_submission' ), 10, 1 );
    }

    /**
     * Handle Bricks form submission.
     *
     * @param object $form The form object.
     */
    public static function handle_form_submission( $form ) {
        $fields = $form->get_fields();
        $formId = $fields['formId'];
        $postId = $fields['postId'];
        $settings = $form->get_settings();

        // Debug log: form submission received
        if (class_exists('Bento_Logger')) {
            Bento_Logger::log('[Bricks] Form submission received. Form ID: ' . $formId . ', Post ID: ' . $postId . '. Fields: ' . print_r($fields, true));
        }

        $event_name = '$bricks_submission.form_id_' . $formId . '.post_id_' . $postId;

        $custom_fields = array_merge(
            array_filter($fields, function($key) {
                return strpos($key, 'form-field') !== 0;
            }, ARRAY_FILTER_USE_KEY),
            [
                'bricks_last_form_id' => $formId,
                'bricks_last_post_id' => $postId
            ]
        );

        // Remove 'bento_' prefix from keys
        $custom_fields = array_combine(
            array_map(function($key) {
                return (strpos($key, 'bento_') === 0) ? substr($key, 6) : $key;
            }, array_keys($custom_fields)),
            array_values($custom_fields)
        );

        // remove nonce, action, formId, postId
        unset($custom_fields['nonce']);
        unset($custom_fields['action']);
        unset($custom_fields['formId']);
        unset($custom_fields['postId']);

        $email = $custom_fields['email'];
        unset($custom_fields['email']);

        if (isset($custom_fields['event']) && !empty($custom_fields['event'])) {
            $event_name = $custom_fields['event'];
            unset($custom_fields['event']);
        }

        // Debug log: event name, email, and custom fields
        if (class_exists('Bento_Logger')) {
            Bento_Logger::log('[Bricks] Prepared event: ' . $event_name . ', Email: ' . $email . ', Custom Fields: ' . print_r($custom_fields, true));
        }

        if (!empty($email)) {
            Bento_Events_Controller::trigger_event(
                null,
                $event_name,
                $email, 
                $fields,
                $custom_fields
            );
            // Debug log: event triggered
            if (class_exists('Bento_Logger')) {
                Bento_Logger::log('[Bricks] Event triggered: ' . $event_name . ' for ' . $email);
            }
        }
    }
}
