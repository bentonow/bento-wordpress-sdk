<?php

namespace Tests\Unit;

use Bento_Events_Controller;
use WooCommerce_Bento_Events;

if (!class_exists('Tests\\Unit\\Test_WC_Product', false)) {
    class Test_WC_Product {
        private $id;
        private $name;
        private $price;
        private $regular_price;
        private $sale_price;
        private $sku;

        public function __construct($id, $name, $price, $regular_price, $sale_price, $sku) {
            $this->id = $id;
            $this->name = $name;
            $this->price = $price;
            $this->regular_price = $regular_price;
            $this->sale_price = $sale_price;
            $this->sku = $sku;
        }

        public function get_id() { return $this->id; }
        public function get_name() { return $this->name; }
        public function get_price() { return $this->price; }
        public function get_regular_price() { return $this->regular_price; }
        public function get_sale_price() { return $this->sale_price; }
        public function get_sku() { return $this->sku; }
        public function get_permalink() { return 'http://example.com/products/' . $this->id; }
    }
}

if (!class_exists('Tests\\Unit\\Test_WC_Order_Item', false)) {
    class Test_WC_Order_Item {
        private $product;
        private $quantity;
        private $line_total;
        private $line_tax;
        private $line_subtotal;
        private $line_subtotal_tax;

        public function __construct($product, $quantity, $line_total, $line_tax, $line_subtotal, $line_subtotal_tax) {
            $this->product = $product;
            $this->quantity = $quantity;
            $this->line_total = $line_total;
            $this->line_tax = $line_tax;
            $this->line_subtotal = $line_subtotal;
            $this->line_subtotal_tax = $line_subtotal_tax;
        }

        public function get_product() { return $this->product; }
        public function get_quantity() { return $this->quantity; }
        public function get_line_total() { return $this->line_total; }
        public function get_line_tax() { return $this->line_tax; }
        public function get_line_subtotal() { return $this->line_subtotal; }
        public function get_line_subtotal_tax() { return $this->line_subtotal_tax; }
    }
}

if (!class_exists('Tests\\Unit\\Test_WC_Order', false)) {
    class Test_WC_Order {
        private $id;
        private $customer_id;
        private $total;
        private $currency;
        private $billing_email;
        private $items;
        private $meta;

        public function __construct($id, $customer_id, $total, $currency, $billing_email, $items, $meta = []) {
            $this->id = $id;
            $this->customer_id = $customer_id;
            $this->total = $total;
            $this->currency = $currency;
            $this->billing_email = $billing_email;
            $this->items = $items;
            $this->meta = $meta;
        }

        public function get_customer_id() { return $this->customer_id; }
        public function get_total() { return $this->total; }
        public function get_currency() { return $this->currency; }
        public function get_billing_email() { return $this->billing_email; }
        public function get_order_key() { return 'order_' . $this->id; }
        public function get_id() { return $this->id; }
        public function get_items() { return $this->items; }
        public function get_meta_data() { return $this->meta; }

        public function get_line_total(Test_WC_Order_Item $item, $include_tax = true, $round = true) {
            return $item->get_line_total();
        }

        public function get_line_tax(Test_WC_Order_Item $item) {
            return $item->get_line_tax();
        }

        public function get_line_subtotal(Test_WC_Order_Item $item, $include_tax = true, $round = true) {
            return $include_tax ? $item->get_line_subtotal() : $item->get_line_subtotal() - $item->get_line_subtotal_tax();
        }
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

test('WooCommerce order completion sends event to Bento', function () {
    global $__wp_test_state;

    $__wp_test_state['wc_customers'][5] = [
        'order_count' => 3,
        'total_spent' => 450.75,
    ];

    $product = new Test_WC_Product(11, 'Sample Product', 25.50, 30.00, 20.00, 'SKU-123');
    $item = new Test_WC_Order_Item($product, 2, 51.00, 5.10, 60.00, 4.00);
    $order = new Test_WC_Order(1001, 5, 51.00, 'USD', 'buyer@example.com', [$item], [ (object) ['key' => 'custom_note', 'value' => 'Priority'] ]);

    $__wp_test_state['wc_orders'][1001] = $order;

    new WooCommerce_Bento_Events();

    $hook = $__wp_test_state['actions']['woocommerce_order_status_completed'][0];
    $callback = $hook['callback'];

    $__wp_test_state['remote_posts'] = [];

    $callback(1001);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$OrderPlaced');
    expect($event['email'])->toBe('buyer@example.com');
    expect($event['details']['cart']['items'][0]['product_name'])->toBe('Sample Product');
    expect($event['details']['value']['amount'])->toBe(5100); // converted cents
});

test('WooCommerce order refund emits refund event with value', function () {
    global $__wp_test_state;

    $__wp_test_state['wc_customers'][9] = [
        'order_count' => 1,
        'total_spent' => 120.00,
    ];

    $product = new Test_WC_Product(22, 'Refundable Product', 40.00, 45.00, 35.00, 'SKU-REF');
    $item = new Test_WC_Order_Item($product, 1, 40.00, 4.00, 40.00, 0.00);
    $order = new Test_WC_Order(2002, 9, 40.00, 'USD', 'refund@example.com', [$item]);
    $__wp_test_state['wc_orders'][2002] = $order;

    $refund_item = new Test_WC_Order_Item($product, 1, -40.00, 0.00, -40.00, 0.00);
    $refund = new Test_WC_Order(3003, 9, -40.00, 'USD', 'refund@example.com', [$refund_item]);
    $__wp_test_state['wc_orders'][3003] = $refund;

    new WooCommerce_Bento_Events();

    $__wp_test_state['remote_posts'] = [];
    $hook = $__wp_test_state['actions']['woocommerce_order_refunded'][0];
    $callback = $hook['callback'];
    $callback(2002, 3003);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$OrderRefunded');
    expect($event['details']['value']['amount'])->toBe(-4000); // converted cents
});

test('WooCommerce order cancellation triggers cancellation event', function () {
    global $__wp_test_state;

    $product = new Test_WC_Product(33, 'Cancelled Product', 15.00, 15.00, 10.00, 'SKU-CAN');
    $item = new Test_WC_Order_Item($product, 1, 15.00, 0.00, 15.00, 0.00);
    $order = new Test_WC_Order(4004, null, 15.00, 'USD', 'cancel@example.com', [$item]);
    $__wp_test_state['wc_orders'][4004] = $order;

    new WooCommerce_Bento_Events();

    $__wp_test_state['remote_posts'] = [];
    $hook = $__wp_test_state['actions']['woocommerce_order_status_cancelled'][0];
    $callback = $hook['callback'];
    $callback(4004);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$OrderCancelled');
    expect($event['email'])->toBe('cancel@example.com');
});
