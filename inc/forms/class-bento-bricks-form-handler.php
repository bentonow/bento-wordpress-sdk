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
     * Sanitize email for logging to prevent PII exposure
     *
     * @param string $email The email to sanitize.
     * @return string Sanitized email for logging.
     */
    private static function sanitize_email_for_logging( $email ) {
        if ( empty( $email ) || ! is_email( $email ) ) {
            return '[invalid-email]';
        }
        
        $parts = explode( '@', $email );
        if ( count( $parts ) !== 2 ) {
            return '[malformed-email]';
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        // Show first 2 chars of username, mask the rest
        $masked_username = substr( $username, 0, 2 ) . str_repeat( '*', max( 0, strlen( $username ) - 2 ) );
        
        return $masked_username . '@' . $domain;
    }

    /**
     * Sanitize form fields for logging to prevent PII exposure
     *
     * @param array $fields The form fields to sanitize.
     * @return string Sanitized fields summary for logging.
     */
    private static function sanitize_fields_for_logging( $fields ) {
        if ( empty( $fields ) || ! is_array( $fields ) ) {
            return '[no-fields]';
        }
        
        $safe_keys = array();
        $sensitive_patterns = array( 'email', 'phone', 'address', 'name', 'first_name', 'last_name', 'user_login', 'password', 'credit', 'card', 'ssn', 'social' );
        
        foreach ( $fields as $key => $value ) {
            $is_sensitive = false;
            foreach ( $sensitive_patterns as $pattern ) {
                if ( stripos( $key, $pattern ) !== false ) {
                    $is_sensitive = true;
                    break;
                }
            }
            
            if ( ! $is_sensitive ) {
                $safe_keys[] = $key;
            }
        }
        
        return '[safe-keys: ' . implode( ', ', $safe_keys ) . ', total-fields: ' . count( $fields ) . ']';
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

        // Debug log: form submission received (sanitized)
        if (class_exists('Bento_Logger')) {
            $sanitized_fields = self::sanitize_fields_for_logging($fields);
            Bento_Logger::log('[Bricks] Form submission received. Form ID: ' . $formId . ', Post ID: ' . $postId . '. Fields: ' . $sanitized_fields);
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

        $email = $custom_fields['email'] ?? '';
        if (isset($custom_fields['email'])) {
            unset($custom_fields['email']);
        }

        if (isset($custom_fields['event']) && !empty($custom_fields['event'])) {
            $event_name = $custom_fields['event'];
            unset($custom_fields['event']);
        }

        // Debug log: event name, email, and custom fields (sanitized)
        if (class_exists('Bento_Logger')) {
            $sanitized_email = self::sanitize_email_for_logging($email);
            $sanitized_custom_fields = self::sanitize_fields_for_logging($custom_fields);
            Bento_Logger::log('[Bricks] Prepared event: ' . $event_name . ', Email: ' . $sanitized_email . ', Custom Fields: ' . $sanitized_custom_fields);
        }

        if (empty($email)) {
            if (class_exists('Bento_Logger')) {
                Bento_Logger::error('[Bricks] Missing primary email field; event will not be sent.');
            }
            return;
        }

        Bento_Events_Controller::trigger_event(
            null,
            $event_name,
            $email, 
            $fields,
            $custom_fields
        );
        // Debug log: event triggered (sanitized)
        if (class_exists('Bento_Logger')) {
            $sanitized_email = self::sanitize_email_for_logging($email);
            Bento_Logger::log('[Bricks] Event triggered: ' . $event_name . ' for ' . $sanitized_email);
        }
    }
}
