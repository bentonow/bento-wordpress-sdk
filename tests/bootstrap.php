<?php

define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../vendor/autoload.php';

// Define required WordPress constants
defined('WP_CONTENT_DIR') || define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

global $__wp_test_state;
$__wp_test_state = [
    'options' => [],
    'transients' => [],
    'deleted_options' => [],
    'deleted_transients' => [],
    'remote_posts' => [],
    'user_meta' => [],
    'users' => [
        'id' => [],
        'email' => [],
        'login' => [],
    ],
    'actions' => [],
    'wc_orders' => [],
    'wc_customers' => [],
    'wcs_subscriptions' => [],
    'edd_orders' => [],
    'edd_downloads' => [],
    'enqueued_scripts' => [],
    'enqueued_styles' => [],
    'inline_scripts' => [],
    'localized_scripts' => [],
    'submenu_pages' => [],
    'checked_nonces' => [],
    'json_success' => [],
    'redirects' => [],
    'scheduled_events' => [],
    'scheduled_events_lookup' => [],
    'unscheduled_events' => [],
    'json' => [],
    'json_error' => [],
    'wc_instance' => null,
    'current_user' => (object) ['ID' => 0, 'user_email' => null],
    'cleared_hooks' => [],
];

if (!function_exists('wp_test_reset_state')) {
    function wp_test_reset_state() {
        global $__wp_test_state;
        $__wp_test_state['deleted_options'] = [];
        $__wp_test_state['deleted_transients'] = [];
        $__wp_test_state['remote_posts'] = [];
        $__wp_test_state['actions'] = [];
        $__wp_test_state['wc_orders'] = [];
        $__wp_test_state['wc_customers'] = [];
        $__wp_test_state['wcs_subscriptions'] = [];
        $__wp_test_state['edd_orders'] = [];
        $__wp_test_state['edd_downloads'] = [];
        $__wp_test_state['enqueued_scripts'] = [];
        $__wp_test_state['enqueued_styles'] = [];
        $__wp_test_state['inline_scripts'] = [];
        $__wp_test_state['localized_scripts'] = [];
        $__wp_test_state['submenu_pages'] = [];
        $__wp_test_state['checked_nonces'] = [];
        $__wp_test_state['json_success'] = [];
        $__wp_test_state['json'] = [];
        $__wp_test_state['json_error'] = [];
        $__wp_test_state['redirects'] = [];
        $__wp_test_state['current_user_can'] = true;
        $__wp_test_state['doing_ajax'] = false;
        $__wp_test_state['scheduled_events'] = [];
        $__wp_test_state['scheduled_events_lookup'] = [];
        $__wp_test_state['unscheduled_events'] = [];
        $__wp_test_state['wc_instance'] = null;
        $__wp_test_state['current_user'] = (object) ['ID' => 0, 'user_email' => null];
        $__wp_test_state['cleared_hooks'] = [];
    }
}

// Stub WordPress functions used in classes
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return ['basedir' => sys_get_temp_dir()];
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($dir) {
        if (is_dir($dir)) {
            return true;
        }
        return mkdir($dir, 0777, true);
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $__wp_test_state;
        return $__wp_test_state['options'][$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $__wp_test_state;
        $__wp_test_state['options'][$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        global $__wp_test_state;
        $__wp_test_state['deleted_options'][] = $option;
        unset($__wp_test_state['options'][$option]);
        return true;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        global $__wp_test_state;
        $__wp_test_state['transients'][$transient] = $value;
        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        global $__wp_test_state;
        return $__wp_test_state['transients'][$transient] ?? false;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        global $__wp_test_state;
        $__wp_test_state['deleted_transients'][] = $transient;
        unset($__wp_test_state['transients'][$transient]);
        return true;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('current_time')) {
    function current_time($format) {
        return date($format);
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = []) {
        global $__wp_test_state;
        $__wp_test_state['remote_posts'][] = ['url' => $url, 'args' => $args];
        if (!empty($__wp_test_state['wp_remote_post_error'])) {
            return new WP_Error('http_request_failed', 'Simulated request failure');
        }

        if (!empty($__wp_test_state['wp_remote_post_response'])) {
            return $__wp_test_state['wp_remote_post_response'];
        }

        return [
            'body' => json_encode(['success' => true]),
            'response' => ['code' => 200],
        ];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        if (is_wp_error($response)) {
            return '';
        }
        return $response['body'] ?? '';
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct(private string $code = '', private string $message = '', private $data = null) {}

        public function get_error_message() {
            return $this->message;
        }

        public function get_error_code() {
            return $this->code;
        }

        public function get_error_data() {
            return $this->data;
        }
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        if (is_wp_error($response)) {
            return 0;
        }
        return $response['response']['code'] ?? 0;
    }
}

if (!function_exists('wp_remote_retrieve_headers')) {
    function wp_remote_retrieve_headers($response) {
        if (is_wp_error($response)) {
            return [];
        }
        return $response['headers'] ?? [];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        global $__wp_test_state;
        $__wp_test_state['actions'][$hook][] = compact('callback', 'priority', 'accepted_args');
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return add_action($hook, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        global $__wp_test_state;
        if (empty($__wp_test_state['actions'][$hook])) {
            return;
        }

        foreach ($__wp_test_state['actions'][$hook] as $registered) {
            $callback = $registered['callback'];
            $accepted_args = $registered['accepted_args'];
            $callback(...array_slice($args, 0, $accepted_args));
        }
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        global $__wp_test_state;
        if (empty($__wp_test_state['actions'][$hook])) {
            return $value;
        }

        foreach ($__wp_test_state['actions'][$hook] as $registered) {
            $callback = $registered['callback'];
            $accepted_args = $registered['accepted_args'];
            $value = $callback(...array_slice([$value, ...$args], 0, $accepted_args));
        }

        return $value;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook) {
        global $__wp_test_state;
        return $__wp_test_state['scheduled_events_lookup'][$hook] ?? false;
    }
}

if (!function_exists('WC')) {
    function WC() {
        global $__wp_test_state;
        if ($__wp_test_state['wc_instance'] === null) {
            $__wp_test_state['wc_instance'] = (object) [
                'session' => null,
                'customer' => null,
                'cart' => null,
            ];
        }

        return $__wp_test_state['wc_instance'];
    }
}

if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event($timestamp, $hook) {
        global $__wp_test_state;
        $__wp_test_state['unscheduled_events'][] = compact('timestamp', 'hook');
        unset($__wp_test_state['scheduled_events_lookup'][$hook]);
        return true;
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook) {
        global $__wp_test_state;
        $__wp_test_state['cleared_hooks'][] = $hook;
        unset($__wp_test_state['scheduled_events_lookup'][$hook]);
        return true;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook) {
        global $__wp_test_state;
        $__wp_test_state['scheduled_events'][] = compact('timestamp', 'recurrence', 'hook');
        $__wp_test_state['scheduled_events_lookup'][$hook] = $timestamp;
        return true;
    }
}

if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 0) {
        return mt_rand($min, $max);
    }
}

if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs(intval($maybeint));
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($path) {
        return 'http://example.com/plugins/' . basename(dirname($path)) . '/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($path) {
        return dirname($path) . '/';
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        // store for reference if needed later
        global $__wp_test_state;
        $__wp_test_state['activation_hook'] = $callback;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        global $__wp_test_state;
        $__wp_test_state['deactivation_hook'] = $callback;
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain() {
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
        global $__wp_test_state;
        $__wp_test_state['enqueued_scripts'][] = compact('handle', 'src', 'deps', 'ver', 'in_footer');
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
        global $__wp_test_state;
        $__wp_test_state['enqueued_styles'][] = compact('handle', 'src', 'deps', 'ver', 'media');
        return true;
    }
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script($handle, $data, $position = 'after') {
        global $__wp_test_state;
        $__wp_test_state['inline_scripts'][] = compact('handle', 'data', 'position');
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return md5($action);
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        global $__wp_test_state;
        $__wp_test_state['localized_scripts'][] = compact('handle', 'object_name', 'l10n');
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        global $__wp_test_state;
        return $__wp_test_state['current_user_can'] ?? true;
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback) {
        global $__wp_test_state;
        $__wp_test_state['submenu_pages'][] = compact('parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback');
        return true;
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $query_arg = '_wpnonce') {
        global $__wp_test_state;
        $__wp_test_state['checked_nonces'][] = compact('action', 'query_arg');
        return true;
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) {
        global $__wp_test_state;
        $__wp_test_state['checked_nonces'][] = [
            'action' => $action,
            'query_arg' => $query_arg,
            'type' => 'ajax',
        ];

        if (!empty($__wp_test_state['fail_ajax_nonce'])) {
            if ($die) {
                throw new RuntimeException('wp_die: check_ajax_referer failed');
            }

            return false;
        }

        return true;
    }
}

if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax() {
        global $__wp_test_state;
        return $__wp_test_state['doing_ajax'] ?? false;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        global $__wp_test_state;
        $__wp_test_state['json_success'][] = $data;
        throw new RuntimeException('wp_send_json_success');
    }
}

if (!function_exists('wp_send_json')) {
    function wp_send_json($data = null) {
        global $__wp_test_state;
        $__wp_test_state['json'][] = $data;
        throw new RuntimeException('wp_send_json');
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($location, $status = 302) {
        global $__wp_test_state;
        $__wp_test_state['redirects'][] = compact('location', 'status');
        throw new RuntimeException('wp_safe_redirect');
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '') {
        throw new RuntimeException('wp_die: ' . $message);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null) {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = null) {
        return $text;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url) {
        $query = http_build_query($args);
        return rtrim($url, '?') . '?' . $query;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        if (is_scalar($str)) {
            return trim(strip_tags((string) $str));
        }

        return '';
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single = true) {
        global $__wp_test_state;
        return $__wp_test_state['user_meta'][$user_id][$key] ?? '';
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $key, $value) {
        global $__wp_test_state;
        $__wp_test_state['user_meta'][$user_id][$key] = $value;
        return true;
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        global $__wp_test_state;
        return $__wp_test_state['users']['id'][$user_id] ?? null;
    }
}

if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) {
        global $__wp_test_state;

        if ('id' === $field) {
            return $__wp_test_state['users']['id'][$value] ?? null;
        }

        if ('email' === $field) {
            return $__wp_test_state['users']['email'][$value] ?? null;
        }

        if ('login' === $field) {
            return $__wp_test_state['users']['login'][$value] ?? null;
        }

        return null;
    }
}

if (!function_exists('get_users')) {
    function get_users($args = []) {
        global $__wp_test_state;
        return $__wp_test_state['users']['list'] ?? [];
    }
}

if (!function_exists('delete_user_meta')) {
    function delete_user_meta($user_id, $key) {
        global $__wp_test_state;
        unset($__wp_test_state['user_meta'][$user_id][$key]);
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        global $__wp_test_state;
        return $__wp_test_state['current_user'] ?? (object) ['ID' => 0, 'user_email' => null];
    }
}

if (!function_exists('wpforms_log')) {
    function wpforms_log() {}
}

if (!function_exists('get_permalink')) {
    function get_permalink($id = 0) {
        return 'http://example.com/?p=' . $id;
    }
}

if (!function_exists('edd_get_currency')) {
    function edd_get_currency() {
        return 'USD';
    }
}

if (!function_exists('email_exists')) {
    function email_exists($email) {
        global $__wp_test_state;
        return isset($__wp_test_state['users']['email'][$email])
            ? $__wp_test_state['users']['email'][$email]->ID
            : false;
    }
}

if (!class_exists('WooCommerce')) {
    class WooCommerce {}
}

if (!class_exists('WC_Order')) {
    class WC_Order {}
}

if (!class_exists('WC_Customer')) {
    class WC_Customer {
        private $id;

        public function __construct($id) {
            $this->id = $id;
        }

        public function get_order_count() {
            global $__wp_test_state;
            return $__wp_test_state['wc_customers'][$this->id]['order_count'] ?? 0;
        }

        public function get_total_spent() {
            global $__wp_test_state;
            return $__wp_test_state['wc_customers'][$this->id]['total_spent'] ?? 0;
        }
    }
}

if (!class_exists('WC_Session_Handler')) {
    class WC_Session_Handler {
        private bool $has_session = false;

        public function init() {
            $this->has_session = true;
        }

        public function has_session() {
            return $this->has_session;
        }

        public function set_customer_session_cookie($force = false) {
            $this->has_session = true;
        }
    }
}

if (!class_exists('WC_Cart')) {
    class WC_Cart {
        private array $items = [];

        public function set_items(array $items): void {
            $this->items = $items;
        }

        public function get_cart() {
            return $this->items;
        }

        public function is_empty() {
            return empty($this->items);
        }

        public function get_cart_contents_count() {
            return count($this->items);
        }
    }
}

if (!function_exists('wc_get_order')) {
    function wc_get_order($order_id) {
        global $__wp_test_state;
        return $__wp_test_state['wc_orders'][$order_id] ?? null;
    }
}

if (!function_exists('wc_get_price_decimals')) {
    function wc_get_price_decimals() {
        return 2;
    }
}

if (!function_exists('get_woocommerce_currency')) {
    function get_woocommerce_currency() {
        return 'USD';
    }
}

if (!class_exists('WC_Subscriptions')) {
    class WC_Subscriptions {}
}

if (!function_exists('wcs_get_subscription')) {
    function wcs_get_subscription($subscription_id) {
        global $__wp_test_state;
        return $__wp_test_state['wcs_subscriptions'][$subscription_id] ?? null;
    }
}

if (!class_exists('SureCart')) {
    class SureCart {}
}

if (!class_exists('SureCart_Models_Order_Stub')) {
    class SureCart_Models_Order_Stub {
        private static $test_order;

        public static function setTestOrder($order) {
            self::$test_order = $order;
        }

        public static function with($relations) {
            return new self();
        }

        public function find($id) {
            return self::$test_order;
        }
    }
}

if (!class_exists('SureCart\Models\Order')) {
    class_alias('SureCart_Models_Order_Stub', 'SureCart\Models\Order');
}

if (!class_exists('Easy_Digital_Downloads')) {
    class Easy_Digital_Downloads {}
}

if (!function_exists('edd_get_order')) {
    function edd_get_order($order_id) {
        global $__wp_test_state;
        return $__wp_test_state['edd_orders'][$order_id] ?? null;
    }
}

if (!function_exists('edd_get_download')) {
    function edd_get_download($download_id) {
        global $__wp_test_state;
        return $__wp_test_state['edd_downloads'][$download_id] ?? null;
    }
}

if (!class_exists('Thrive_Dash_List_Connection_Abstract')) {
    class Thrive_Dash_List_Connection_Abstract {
        protected $credentials = [];

        public function __construct($key = null) {}
        public function is_connected() { return true; }
        protected function post($key, $default = []) { return $default; }
        protected function set_credentials($creds) { $this->credentials = $creds; }
        protected function save() {}
        protected function success($message) { return ['message' => $message]; }
        protected function get_name_parts($name) {
            $parts = explode(' ', trim($name), 2);
            return [$parts[0] ?? '', $parts[1] ?? ''];
        }
    }
}

if (!class_exists('Thrive_Dash_List_Manager')) {
    class Thrive_Dash_List_Manager {
        public static $should_include = true;
        public static function should_include_api($connection, $api_filter) {
            return self::$should_include;
        }
    }
}

if (!class_exists('Thrive_Dash_Api_Bento_Exception')) {
    class Thrive_Dash_Api_Bento_Exception extends \Exception {}
}

if (!class_exists('WPForms_Provider')) {
    class WPForms_Provider {
        protected $version;
        protected $name;
        protected $slug;
        protected $priority;
        protected $icon;
        public function init() {}
        protected function connect_request() {}
        protected function process_conditionals($fields, $entry, $form_data, $connection) {
            return true;
        }
        protected function builder_settings_default_content($content) {
            return $content;
        }
        public function connect_dismiss() {}
    }
}

if (!class_exists('GFForms')) {
    class GFForms {}
}

if (!class_exists('GFCommon')) {
    class GFCommon {
        public static $version = '2.7.0';
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        global $__wp_test_state;
        $__wp_test_state['json_error'][] = $data;
        throw new RuntimeException('wp_send_json_error');
    }
}

if (!class_exists('ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar')) {
    class ElementorPro_Modules_Forms_Registrars_Form_Actions_Registrar {
        public $registered = [];
        public function register($action) {
            $this->registered[] = $action;
        }
    }
    class_alias('ElementorPro_Modules_Forms_Registrars_Form_Actions_Registrar', 'ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar');
}

if (!class_exists('ElementorPro\Modules\Forms\Classes\Action_Base')) {
    abstract class ElementorPro_Modules_Forms_Classes_Action_Base {
        abstract public function get_name();
        abstract public function get_label();
        public function register_settings_section($widget) {}
        public function run($record, $ajax_handler) {}
        public function on_export($element) { return $element; }
    }
    class_alias('ElementorPro_Modules_Forms_Classes_Action_Base', 'ElementorPro\Modules\Forms\Classes\Action_Base');
}

if (!class_exists('Elementor\Controls_Manager')) {
    class Elementor_Controls_Manager {
        const TEXT = 'text';
        const SWITCHER = 'switcher';
    }
    class_alias('Elementor_Controls_Manager', 'Elementor\Controls_Manager');
}

if (!class_exists('Elementor\Widget_Base')) {
    class Elementor_Widget_Base {
        public $sections = [];
        public $controls = [];
        public function start_controls_section($id, $args) { $this->sections[] = compact('id', 'args'); }
        public function add_control($id, $config) { $this->controls[$id] = $config; }
        public function end_controls_section() {}
    }
    class_alias('Elementor_Widget_Base', 'Elementor\Widget_Base');
}

if (!class_exists('ElementorPro\Modules\Forms\Classes\Form_Record')) {
    class ElementorPro_Modules_Forms_Classes_Form_Record {
        private $data;
        public function __construct($data) { $this->data = $data; }
        public function get($key) { return $this->data[$key]; }
    }
    class_alias('ElementorPro_Modules_Forms_Classes_Form_Record', 'ElementorPro\Modules\Forms\Classes\Form_Record');
}

if (!class_exists('ElementorPro\Modules\Forms\Classes\Ajax_Handler')) {
    class ElementorPro_Modules_Forms_Classes_Ajax_Handler {}
    class_alias('ElementorPro_Modules_Forms_Classes_Ajax_Handler', 'ElementorPro\Modules\Forms\Classes\Ajax_Handler');
}

if (!class_exists('ElementorPro\Core\Utils')) {
    class ElementorPro_Core_Utils {
        public static function get_client_ip() { return '127.0.0.1'; }
    }
    class_alias('ElementorPro_Core_Utils', 'ElementorPro\Core\Utils');
}
