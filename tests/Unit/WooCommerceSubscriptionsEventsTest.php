<?php

namespace Tests\Unit;

use WooCommerce_Subscriptions_Bento_Events;
use Bento_Events_Controller;

require_once __DIR__ . '/WooCommerceEventsTest.php';

if (!class_exists('Tests\\Unit\\Test_WC_Subscription')) {
    class Test_WC_Subscription extends Test_WC_Order {
        private $status;
        private $subscription_id;

        public function __construct($subscription_id, $customer_id, $total, $currency, $billing_email, $items, $status = 'active') {
            parent::__construct($subscription_id, $customer_id, $total, $currency, $billing_email, $items);
            $this->status = $status;
            $this->subscription_id = $subscription_id;
        }

        public function get_id() { return $this->subscription_id; }
        public function get_status() { return $this->status; }
        public function get_last_order($type = 'all') { return $this; }
    }
}

beforeEach(function () {
    wp_test_reset_state();

    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, [
        'bento_site_key' => 'site-key',
        'bento_publishable_key' => 'pub-key',
        'bento_secret_key' => 'secret-key',
    ]);
});

afterEach(function () {
    $reflection = new \ReflectionClass(Bento_Events_Controller::class);
    $property = $reflection->getProperty('bento_options');
    $property->setAccessible(true);
    $property->setValue(null, []);
});

test('WooCommerce subscription activation triggers Bento event', function () {
    global $__wp_test_state;

    $product = new Test_WC_Product(55, 'Subscription Product', 15.00, 15.00, 12.00, 'SUB-001');
    $item = new Test_WC_Order_Item($product, 1, 15.00, 1.50, 15.00, 0.00);
    $subscription = new Test_WC_Subscription(2002, 7, 15.00, 'USD', 'subscriber@example.com', [$item], 'active');

    $__wp_test_state['wcs_subscriptions'][999] = $subscription;

    new WooCommerce_Subscriptions_Bento_Events();

    $__wp_test_state['remote_posts'] = [];
    $hook = $__wp_test_state['actions']['woocommerce_subscription_status_active'][0];
    $callback = $hook['callback'];
    $callback($subscription);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);

    expect($payload['events'][0]['type'])->toBe('$SubscriptionActive');
    expect($payload['events'][0]['email'])->toBe('subscriber@example.com');
    expect($payload['events'][0]['details']['subscription']['status'])->toBe('active');
});

test('WooCommerce subscription renewal triggers Bento event', function () {
    global $__wp_test_state;

    $product = new Test_WC_Product(66, 'Renewal Product', 20.00, 20.00, 18.00, 'SUB-RENEW');
    $item = new Test_WC_Order_Item($product, 1, 20.00, 0.00, 20.00, 0.00);
    $subscription = new Test_WC_Subscription(3003, 12, 20.00, 'USD', 'renew@example.com', [$item], 'active');
    $__wp_test_state['wcs_subscriptions'][3003] = $subscription;

    new WooCommerce_Subscriptions_Bento_Events();

    $__wp_test_state['remote_posts'] = [];
    $hook = $__wp_test_state['actions']['woocommerce_scheduled_subscription_payment'][0];
    $callback = $hook['callback'];
    $callback(3003);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$SubscriptionRenewed');
    expect($event['email'])->toBe('renew@example.com');
    expect($event['details']['subscription']['order']['items'][0]['product_name'])->toBe('Renewal Product');
});
