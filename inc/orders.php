<?php

/**
 * This class loads Bento's orders events.
 */
class Bento_Helper_Orders
{
  protected $apiUrl = 'https://app.bentonow.com';

  /**
   * Constructor.
   */
  public function __construct()
  {
    add_action('woocommerce_thankyou', [$this, 'send_order_placed_event']);
    add_action('woocommerce_order_status_completed', [
      $this,
      'send_order_shipped_event',
    ]);
    add_action('woocommerce_order_status_cancelled', [
      $this,
      'send_order_cancelled_event',
    ]);
    add_action('woocommerce_order_refunded', [
      $this,
      'send_order_refunded_event',
    ]);
  }

  public function send_order_placed_event($order_id)
  {
    $order = wc_get_order($order_id);

    $this->sendEvent('$woocommerceOrderPlaced', $order);
  }

  public function send_order_shipped_event($order_id)
  {
    $order = wc_get_order($order_id);

    $this->sendEvent('$woocommerceOrderShipped', $order);
  }

  public function send_order_cancelled_event($order_id)
  {
    $order = wc_get_order($order_id);

    $this->sendEvent('$woocommerceOrderCancelled', $order);
  }

  public function send_order_refunded_event($order_id)
  {
    $order = wc_get_order($order_id);

    $this->sendEvent('$woocommerceOrderRefunded', $order);
  }

  private function sendEvent($name, $order)
  {
    $bento_options = get_option('bento_settings');
    $bento_site_key = $bento_options['bento_site_key'];
    $base_currency = get_woocommerce_currency();

    if (empty($bento_site_key)) {
      return;
    }

    $line_items = $order->get_items();
    $items = [];

    foreach ($line_items as $item) {
      $product = $order->get_product_from_item($item);

      $product_item = [];

      $product_item['product_name'] = $product->get_name();
      $product_item['product_permalink'] = $product->get_permalink();
      $product_item['product_price'] = $product->get_price();
      $product_item['product_regular_price'] = $product->get_regular_price();
      $product_item['product_sale_price'] = $product->get_sale_price();
      $product_item['product_sku'] = $product->get_sku();
      $product_item['shop_base_currency'] = $base_currency;
      $product_item['quantity'] = $item['qty'];
      $product_item['product_id'] = $product->get_id();
      $product_item['line_total'] = $order->get_line_total($item, true, true);
      $product_item['line_tax'] = $order->get_line_tax($item);
      $product_item['line_subtotal'] = $order->get_line_subtotal(
        $item,
        true,
        true
      );
      $product_item['line_subtotal_tax'] =
        $order->get_line_subtotal($item, true, true) -
        $order->get_line_subtotal($item, false, true);

      array_push($items, $product_item);
    }

    $data = [
      'site' => $bento_site_key,
      'type' => $name,
      'email' => $order->get_billing_email(),
      'details' => [
        'value' => [
          'amount' => $order->get_total(),
          'currency' => $order->get_currency(),
        ],
        'unique' => [
          'key' => $order->get_order_key(),
        ],
        'cart' => [
          'items' => $items,
        ],
      ],
    ];

    $response = wp_remote_post($this->apiUrl . '/tracking/generic', [
      'body' => $data,
    ]);
  }
}

new Bento_Helper_Orders();
