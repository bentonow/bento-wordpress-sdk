<?php

namespace Tests\Unit;

use SureCart_Bento_Events;
use Bento_Events_Controller;

if (!class_exists('Tests\\Unit\\Test_SureCart_Customer')) {
    class Test_SureCart_Customer {
        public $id;
        public function __construct($id) { $this->id = $id; }
    }
}

if (!class_exists('Tests\\Unit\\Test_SureCart_Checkout')) {
    class Test_SureCart_Checkout {
        public $id = 'chk_123';
        public $total_amount = 9900;
        public $currency = 'USD';
        public $status = 'paid';
        public $customer;
        public $email = 'scustomer@example.com';
        public $metadata = ['coupon' => 'WELCOME'];
        public $first_name = 'Casey';
        public $last_name = 'Customer';
        public $name = 'Casey Customer';
        public $phone = '1234567890';

        public function __construct() {
            $this->customer = new Test_SureCart_Customer(88);
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

test('SureCart checkout confirmation pushes Bento event with metadata', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $controller = new SureCart_Bento_Events();
    $checkout = new Test_SureCart_Checkout();

    $controller->handle_checkout_confirmed($checkout, null);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$CheckoutConfirmed');
    expect($event['email'])->toBe('scustomer@example.com');
    expect($event['details']['value']['amount'])->toBe(9900);
    expect($event['fields']['coupon'])->toBe('WELCOME');
    expect($event['fields']['first_name'])->toBe('Casey');
});

test('SureCart checkout handles scalar metadata gracefully', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $controller = new SureCart_Bento_Events();
    $checkout = new Test_SureCart_Checkout();
    $checkout->metadata = 'raw-string';

    $controller->handle_checkout_confirmed($checkout, null);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $fields = $payload['events'][0]['fields'];

    expect($fields)->not->toHaveKey('coupon');
    expect($fields['first_name'])->toBe('Casey');
});

test('SureCart checkout skips event when email missing', function () {
    global $__wp_test_state;
    $__wp_test_state['remote_posts'] = [];

    $controller = new SureCart_Bento_Events();
    $checkout = new Test_SureCart_Checkout();
    $checkout->email = '';

    $controller->handle_checkout_confirmed($checkout, null);

    expect($__wp_test_state['remote_posts'])->toBeEmpty();
});

test('SureCart order detail hydration includes line items', function () {
    $controller = new SureCart_Bento_Events();

    $product = (object) [
        'id' => 'prod_1',
        'name' => 'Line Item Product',
        'sku' => 'SC-001',
    ];

    $order = (object) [
        'id' => 'ord_1',
        'line_items' => [
            (object) [
                'price' => (object) [
                    'product' => $product,
                    'amount' => 4900,
                    'currency' => 'USD',
                ],
                'quantity' => 2,
                'total' => 9800,
            ],
        ],
    ];

    \SureCart\Models\Order::setTestOrder($order);

    $reflection = new \ReflectionClass(SureCart_Bento_Events::class);
    $method = $reflection->getMethod('prepare_order_event_details');
    $method->setAccessible(true);

    $details = $method->invoke($controller, $order);

    expect($details['cart']['items'][0]['product_name'])->toBe('Line Item Product');
    expect($details['cart']['items'][0]['total_amount'])->toBe(9800);
});
