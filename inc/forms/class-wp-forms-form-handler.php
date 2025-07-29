<?php
/**
 * Simple WPForms Bento Integration
 * Replaces the complex provider with direct form submission handling
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPForms_Bento_Integration {

    public function __construct() {
        if (!class_exists('WPForms')) {
            return;
        }

        add_action('wpforms_process_complete', [$this, 'handle_form_submission'], 10, 4);
        add_action('wpforms_form_settings_notifications', [$this, 'add_form_settings']);
        add_filter('wpforms_builder_settings_sections', [$this, 'add_settings_section']);
    }

    /**
     * Handle form submission and trigger Bento event
     */
    public function handle_form_submission($fields, $entry, $form_data, $entry_id) {
        $email = $this->extract_email($fields);
        if (!$email) {
            if (class_exists('Bento_Logger')) {
                Bento_Logger::warning('[WPForms] Form submission skipped - no email found in form ID: ' . $form_data['id']);
            }
            return;
        }

        $event_name = $this->get_event_name($form_data);
        $custom_fields = $this->build_custom_fields($fields, $form_data);
        $event_details = $this->build_event_details($form_data, $entry_id);

        // Log the event being triggered
        if (class_exists('Bento_Logger')) {
            $sanitized_email = $this->sanitize_email_for_logging($email);
            $sanitized_fields = $this->sanitize_fields_for_logging($custom_fields);
            Bento_Logger::info('[WPForms] Triggering event: ' . $event_name . ' for email: ' . $sanitized_email . ' with fields: ' . $sanitized_fields);
        }

        $result = Bento_Events_Controller::trigger_event(
            get_current_user_id(),
            $event_name,
            $email,
            $event_details,
            $custom_fields
        );

        if (class_exists('Bento_Logger')) {
            $sanitized_email = $this->sanitize_email_for_logging($email);
            if ($result) {
                Bento_Logger::info('[WPForms] Event triggered successfully: ' . $event_name . ' for ' . $sanitized_email);
            } else {
                Bento_Logger::error('[WPForms] Failed to trigger event: ' . $event_name . ' for ' . $sanitized_email);
            }
        }

        wpforms_log(
            'Bento Event Triggered',
            ['event' => $event_name, 'email' => $email, 'success' => $result],
            ['type' => ['provider'], 'parent' => $entry_id, 'form_id' => $form_data['id']]
        );
    }

    /**
     * Extract email from form fields
     */
    private function extract_email($fields) {
        foreach ($fields as $field) {
            if ($field['type'] === 'email' && !empty($field['value'])) {
                return sanitize_email($field['value']);
            }
        }
        return null;
    }

    /**
     * Generate event name from form
     */
    private function get_event_name($form_data) {
        $custom_event = $form_data['settings']['bento_event_name'] ?? '';

        if (!empty($custom_event)) {
            return sanitize_text_field($custom_event);
        }

        $form_title = $form_data['settings']['form_title'] ?? 'Form';
        return '$wpforms.' . sanitize_title($form_title);
    }

    /**
     * Build event details for Bento
     */
    private function build_event_details($form_data, $entry_id) {
        return [
            'form_id' => $form_data['id'],
            'form_title' => $form_data['settings']['form_title'] ?? '',
            'entry_id' => $entry_id,
            'ipaddress' => $this->get_client_ip(),
            'referrer' => isset($_POST['referrer']) ? sanitize_text_field($_POST['referrer']) : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
        ];
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Build custom fields from form submission
     */
    private function build_custom_fields($fields, $form_data) {
        $custom_fields = [];
        $field_mapping = $form_data['settings']['bento_field_mapping'] ?? [];

        foreach ($fields as $field_id => $field) {
            if ($field['type'] === 'email') {
                continue; // Email handled separately
            }

            $field_names = $this->get_field_names($field_id, $field, $field_mapping);
            $field_values = $this->get_field_values($field);

            // Handle multiple field names/values (for name fields)
            if (is_array($field_names) && is_array($field_values)) {
                foreach ($field_names as $index => $field_name) {
                    if ($field_name && isset($field_values[$index]) && $field_values[$index] !== null) {
                        $custom_fields[$field_name] = $field_values[$index];
                    }
                }
            } else {
                // Single field name/value
                if ($field_names && $field_values !== null) {
                    $custom_fields[$field_names] = $field_values;
                }
            }
        }

        return $custom_fields;
    }

    /**
     * Get field names for Bento (mapped or default)
     */
    private function get_field_names($field_id, $field, $field_mapping) {
        // Use custom mapping if set
        if (!empty($field_mapping[$field_id])) {
            return sanitize_key($field_mapping[$field_id]);
        }

        // Handle special field types
        $label = $field['name'] ?? $field['label'] ?? '';

        // Handle name fields specifically
        if ($field['type'] === 'name') {
            $format = $field['format'] ?? 'simple';
            
            switch ($format) {
                case 'first-last':
                    return ['first_name', 'last_name'];
                case 'first-middle-last':
                    return ['first_name', 'middle_name', 'last_name'];
                case 'simple':
                default:
                    return 'name';
            }
        }

        if (empty($label)) {
            return "field_$field_id";
        }

        return sanitize_key(strtolower(str_replace(' ', '_', $label)));
    }

    /**
     * Extract and format field values
     */
    private function get_field_values($field) {
        if (empty($field['value'])) {
            return null;
        }

        // Handle name fields with multiple values
        if ($field['type'] === 'name' && is_array($field['value'])) {
            $format = $field['format'] ?? 'simple';
            
            switch ($format) {
                case 'first-last':
                    return [
                        sanitize_text_field($field['value']['first'] ?? ''),
                        sanitize_text_field($field['value']['last'] ?? '')
                    ];
                case 'first-middle-last':
                    return [
                        sanitize_text_field($field['value']['first'] ?? ''),
                        sanitize_text_field($field['value']['middle'] ?? ''),
                        sanitize_text_field($field['value']['last'] ?? '')
                    ];
                case 'simple':
                default:
                    return sanitize_text_field($field['value']);
            }
        }

        // Handle arrays (checkboxes, multiple select)
        if (is_array($field['value'])) {
            return implode(', ', array_filter($field['value']));
        }

        return sanitize_text_field($field['value']);
    }

    /**
     * Sanitize email for logging to prevent PII exposure
     */
    private function sanitize_email_for_logging($email) {
        if (empty($email) || !is_email($email)) {
            return '[invalid-email]';
        }
        
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '[malformed-email]';
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        // Handle short usernames properly
        $username_length = strlen($username);
        if ($username_length === 0) {
            $masked_username = '*';
        } elseif ($username_length === 1) {
            $masked_username = $username . '*';
        } else {
            // Show first 2 chars of username, mask the rest
            $masked_username = substr($username, 0, 2) . str_repeat('*', $username_length - 2);
        }
        
        return $masked_username . '@' . $domain;
    }

    /**
     * Sanitize fields for logging to prevent PII exposure
     */
    private function sanitize_fields_for_logging($fields) {
        if (empty($fields) || !is_array($fields)) {
            return '[]';
        }

        $safe_keys = [];
        $sensitive_keys = [];
        $total_fields = count($fields);

        foreach (array_keys($fields) as $key) {
            $lower_key = strtolower($key);
            if (strpos($lower_key, 'name') !== false || 
                strpos($lower_key, 'address') !== false || 
                strpos($lower_key, 'phone') !== false || 
                strpos($lower_key, 'password') !== false ||
                strpos($lower_key, 'credit') !== false ||
                strpos($lower_key, 'card') !== false) {
                $sensitive_keys[] = $key;
            } else {
                $safe_keys[] = $key;
            }
        }

        $result = 'Safe fields: [' . implode(', ', array_slice($safe_keys, 0, 5)) . ']';
        if (count($safe_keys) > 5) {
            $result .= ' (+' . (count($safe_keys) - 5) . ' more)';
        }
        
        if (!empty($sensitive_keys)) {
            $result .= ', Sensitive fields: [' . implode(', ', array_slice($sensitive_keys, 0, 3)) . ']';
            if (count($sensitive_keys) > 3) {
                $result .= ' (+' . (count($sensitive_keys) - 3) . ' more)';
            }
        }
        
        $result .= ' (Total: ' . $total_fields . ' fields)';
        
        return $result;
    }

    /**
     * Add Bento settings section to form builder
     */
    public function add_settings_section($sections) {
        $sections['bento'] = __('Bento', 'wpforms-lite');
        return $sections;
    }

    /**
     * Add Bento settings fields to form builder
     */
    public function add_form_settings($instance) {
        echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-bento">';
        echo '<div class="wpforms-panel-content-section-title">' . __('Bento Integration', 'wpforms-lite') . '</div>';

        // Enable toggle
        wpforms_panel_field(
            'toggle',
            'settings',
            'bento_enable',
            $instance->form_data,
            __('Enable Bento Integration', 'wpforms-lite')
        );

        // Custom event name
        wpforms_panel_field(
            'text',
            'settings',
            'bento_event_name',
            $instance->form_data,
            __('Event Name', 'wpforms-lite'),
            [
                'placeholder' => '$wpforms.contact_form',
                'tooltip' => __('Custom event name. Leave empty to auto-generate from form title.', 'wpforms-lite')
            ]
        );

        echo '</div>';
    }
}

new WPForms_Bento_Integration();