<?php

namespace Tests\Unit;

use Bento_Events_Controller;
use EDD_Bento_Events;

if (!class_exists(__NAMESPACE__ . '\\EddTestOrder')) {
    class EddTestOrder {
        public $id;
        public $email;
        public $currency;
        public $total;
        public $user_id;
        private $items = [];

        public function __construct($id, $email, $currency, $total, $user_id = null) {
            $this->id = $id;
            $this->email = $email;
            $this->currency = $currency;
            $this->total = $total;
            $this->user_id = $user_id;
        }

        public function add_item(EddTestOrderItem $item) {
            $this->items[] = $item;
        }

        public function get_items() {
            return $this->items;
        }

        public function get_number() {
            return 'edd-' . $this->id;
        }
    }
}

if (!class_exists(__NAMESPACE__ . '\\EddTestOrderItem')) {
    class EddTestOrderItem {
        public $product_id;
        public $product_name;
        public $amount;
        public $quantity;

        public function __construct($product_id, $product_name, $amount, $quantity) {
            $this->product_id = $product_id;
            $this->product_name = $product_name;
            $this->amount = $amount;
            $this->quantity = $quantity;
        }
    }
}

if (!class_exists('EDD\\Orders\\Order')) {
    class_alias(EddTestOrder::class, 'EDD\\Orders\\Order');
}

if (!class_exists('EDD\\Orders\\Order_Item')) {
    class_alias(EddTestOrderItem::class, 'EDD\\Orders\\Order_Item');
}

if (!class_exists('EDD_Download')) {
    class EDD_Download {
        private $name;
        private $price;
        private $sku;

        public function __construct($name, $price, $sku) {
            $this->name = $name;
            $this->price = $price;
            $this->sku = $sku;
        }

        public function get_name() { return $this->name; }
        public function get_price() { return $this->price; }
        public function get_sku() { return $this->sku; }
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

test('EDD purchase completion fires Bento download event', function () {
    global $__wp_test_state;

    $__wp_test_state['users']['email']['edd@example.com'] = (object) ['ID' => 314];

    $order = new EddTestOrder(501, 'edd@example.com', 'USD', 29.99, 314);
    $item = new EddTestOrderItem(77, 'Ebook', 29.99, 1);
    $order->add_item($item);

    $__wp_test_state['edd_orders'][501] = $order;
    $__wp_test_state['edd_downloads'][77] = new EDD_Download('Ebook', 29.99, 'EBK-001');

    new EDD_Bento_Events();

    $__wp_test_state['remote_posts'] = [];
    $hook = $__wp_test_state['actions']['edd_complete_purchase'][0];
    $callback = $hook['callback'];
    $callback(501);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $payload = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true);
    $event = $payload['events'][0];

    expect($event['type'])->toBe('$DownloadPurchased');
    expect($event['email'])->toBe('edd@example.com');
    expect($event['details']['cart']['items'][0]['product_name'])->toBe('Ebook');
    expect($event['details']['value']['amount'])->toBe(29.99);
});

test('EDD verified download event captures file metadata', function () {
    global $__wp_test_state;

    $__wp_test_state['edd_downloads'][88] = new EDD_Download('Song Pack', 9.99, 'MP3-001');

    new EDD_Bento_Events();

    $__wp_test_state['remote_posts'] = [];
    $hook = $__wp_test_state['actions']['edd_process_verified_download'][0];
    $callback = $hook['callback'];
    $callback(88, 'listener@example.com');

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $event = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true)['events'][0];

    expect($event['type'])->toBe('$DownloadDownloaded');
    expect($event['email'])->toBe('listener@example.com');
    expect($event['details']['download']['sku'])->toBe('MP3-001');
});

test('EDD refund emits negative value event', function () {
    global $__wp_test_state;

    $order = new EddTestOrder(601, 'customer@example.com', 'USD', 49.99, 101);
    $item = new EddTestOrderItem(90, 'Theme', 49.99, 1);
    $order->add_item($item);

    $refund = new EddTestOrder(701, 'customer@example.com', 'USD', -49.99, 101);
    $refund_item = new EddTestOrderItem(90, 'Theme', -49.99, 1);
    $refund->add_item($refund_item);

    $__wp_test_state['edd_orders'][601] = $order;
    $__wp_test_state['edd_orders'][701] = $refund;
    $__wp_test_state['edd_downloads'][90] = new EDD_Download('Theme', 49.99, 'THEME-001');

    new EDD_Bento_Events();

    $__wp_test_state['remote_posts'] = [];
    $hook = $__wp_test_state['actions']['edd_refund_order'][0];
    $callback = $hook['callback'];
    $callback(601, 701, true);

    expect($__wp_test_state['remote_posts'])->toHaveCount(1);
    $event = json_decode($__wp_test_state['remote_posts'][0]['args']['body'], true)['events'][0];

    expect($event['type'])->toBe('$DownloadRefunded');
    expect($event['details']['value']['amount'])->toBe(-49.99);
});
