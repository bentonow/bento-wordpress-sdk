<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../inc/ajax.php';

class Test_Product
{
    public function __construct(private array $data) {}

    public function get_name() { return $this->data['name']; }
    public function get_permalink() { return $this->data['permalink']; }
    public function get_price() { return $this->data['price']; }
    public function get_regular_price() { return $this->data['regular_price']; }
    public function get_sale_price() { return $this->data['sale_price']; }
    public function get_sku() { return $this->data['sku']; }
}

class Session_Without_Cookie
{
    public bool $cookie_set = false;

    public function has_session() { return false; }
    public function set_customer_session_cookie($force = true) { $this->cookie_set = true; }
}

beforeEach(function () {
    wp_test_reset_state();
    $_POST = [];
});

test('bento_get_cart_items returns empty array when cart is empty', function () {
    $ajax = new \Bento_Ajax();

    $wc = \WC();
    $wc->session = null;
    $wc->cart = new \WC_Cart();

    ob_start();

    expect(function () use ($ajax) {
        $ajax->bento_get_cart_items();
    })->toThrow(\RuntimeException::class, 'wp_die: ');

    $output = ob_get_clean();
    expect($output)->toBe('[]');
});

test('bento_get_cart_items returns cart payload with product details', function () {
    $ajax = new \Bento_Ajax();

    $product = new Test_Product([
        'name' => 'Sample Product',
        'permalink' => 'http://example.com/sample-product',
        'price' => '9.99',
        'regular_price' => '12.99',
        'sale_price' => '8.99',
        'sku' => 'SKU-123',
    ]);

    $item = [
        'data' => $product,
        'quantity' => 2,
    ];

    $wc = \WC();
    $cart = new \WC_Cart();
    $cart->set_items([$item]);
    $wc->cart = $cart;
    $wc->session = null;

    ob_start();

    expect(function () use ($ajax) {
        $ajax->bento_get_cart_items();
    })->toThrow(\RuntimeException::class, 'wp_die: ');

    $output = ob_get_clean();
    $payload = json_decode($output, true);

    expect($payload)->toHaveCount(1);
    expect($payload[0]['product_name'])->toBe('Sample Product');
    expect($payload[0]['product_permalink'])->toBe('http://example.com/sample-product');
    expect($payload[0]['product_price'])->toBe('9.99');
    expect($payload[0]['quantity'])->toBe(2);
    expect($payload[0]['shop_base_currency'])->toBe('USD');
});

test('bento_get_cart_items sets customer session cookie when session missing', function () {
    $ajax = new \Bento_Ajax();

    $wc = \WC();
    $wc->session = new Session_Without_Cookie();
    $wc->cart = new \WC_Cart();
    $wc->cart->set_items([[ 'data' => new Test_Product([
        'name' => 'Any',
        'permalink' => '#',
        'price' => '1.00',
        'regular_price' => '1.00',
        'sale_price' => '1.00',
        'sku' => 'ANY',
    ]) ]]);

    ob_start();

    expect(function () use ($ajax) {
        $ajax->bento_get_cart_items();
    })->toThrow(\RuntimeException::class, 'wp_die: ');

    ob_end_clean();

    expect($wc->session->cookie_set)->toBeTrue();
});
