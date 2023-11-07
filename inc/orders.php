<?php

/**
 * This class loads Bento's orders events.
 */
class Bento_Helper_Orders
{
  protected $apiUrl = 'https://app.bentonow.com';
  protected $eventsWithValue = [
    '$OrderPlaced',
    '$OrderCancelled',
    '$OrderRefunded',
  ];

  /**
   * Constructor.
   */
  public function __construct()
  {
     // These are standard WooCommerce hooks
    // https://woocommerce.com/document/subscriptions/develop/action-reference/
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

    // These are WooCommerce Subscription hooks
    // https://woocommerce.com/document/subscriptions/develop/action-reference/
    add_action('woocommerce_checkout_subscription_created', [
      $this,
      'send_checkout_subscription_created_event',
    ]);

    add_action('woocommerce_subscription_status_active', [
      $this,
      'send_woocommerce_subscription_status_active_event',
    ]);

    add_action('woocommerce_subscription_status_cancelled', [
      $this,
      'send_woocommerce_subscription_status_cancelled_event',
    ]);

    add_action('woocommerce_subscription_status_expired', [
      $this,
      'send_woocommerce_subscription_status_expired_event',
    ]);

    add_action('woocommerce_subscription_status_on-hold', [
      $this,
      'send_woocommerce_subscription_status_on_hold_event',
    ]);

    add_action('woocommerce_scheduled_subscription_trial_end-hold', [
      $this,
      'send_woocommerce_scheduled_subscription_trial_end_event',
    ]);

  }

  public function send_woocommerce_scheduled_subscription_trial_end_event($subscription)
  {
    $this->sendSubscriptionEvent('$SubscriptionTrialEnded', $subscription);
  }

  public function send_woocommerce_subscription_status_on_hold_event($subscription)
  {
    $this->sendSubscriptionEvent('$SubscriptionOnHold', $subscription);
  }

  public function send_woocommerce_subscription_status_expired_event($subscription)
  {
    $this->sendSubscriptionEvent('$SubscriptionExpired', $subscription);
  }

  public function send_woocommerce_subscription_status_cancelled_event($subscription)
  {
    $this->sendSubscriptionEvent('$SubscriptionCancelled', $subscription);
  }

  public function send_woocommerce_subscription_status_active_event($subscription)
  {
    $order =
    $this->sendSubscriptionEvent('$SubscriptionActive', $subscription);
  }

  public function send_checkout_subscription_created_event($subscription)
  {
    $this->sendSubscriptionEvent('$SubscriptionCreated', $subscription);
  }

  public function send_order_placed_event($order_id)
  {
    $order = wc_get_order($order_id);

    $this->sendEvent('$OrderPlaced', $order);
  }

  public function send_order_shipped_event($order_id)
  {
    $order = wc_get_order($order_id);

    $this->sendEvent('$OrderShipped', $order);
  }

  public function send_order_cancelled_event($order_id)
  {
    $order = wc_get_order($order_id);

    $this->sendEvent('$OrderCancelled', $order);
  }

  public function send_order_refunded_event($order_id)
  {
    $order = wc_get_order($order_id);

    $this->sendEvent('$OrderRefunded', $order);
  }

  private function sendSubscriptionEvent($name, $subscription)
  {
    $bento_options = get_option('bento_settings');

    if (
      ! $bento_options ||
      ! array_key_exists( 'bento_site_key', $bento_options ) ||
      empty( $bento_options['bento_site_key'] )
    ) {
      return;
    }

    $bento_site_key = $bento_options['bento_site_key'];

    // $details = [
    //  'cart' => [
    //    'items' => $this->getOrderItems($subscription),
    // ],
    // ];

    // if (in_array($name, $this->eventsWithValue)) {
    //  $details = $this->setEventValue($name, $details, $order);
    // }

    $data = [
      'site' => $bento_site_key,
      'type' => $name,
      'email' => $subscription->get_billing_email(),
      'details' => $details,
    ];

    $response = wp_remote_post($this->apiUrl . '/tracking/generic', [
      'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
      'body' => json_encode($data),
      'method' => 'POST',
      'data_format' => 'body',
    ]);
  }

  private function sendEvent($name, $order)
  {
    $bento_options = get_option('bento_settings');

    if (
      ! $bento_options ||
      ! array_key_exists( 'bento_site_key', $bento_options ) ||
      empty( $bento_options['bento_site_key'] )
    ) {
      return;
    }

    $bento_site_key = $bento_options['bento_site_key'];

    $details = [
      'cart' => [
        'items' => $this->getOrderItems($order),
      ],
    ];

    if (in_array($name, $this->eventsWithValue)) {
      $details = $this->setEventValue($name, $details, $order);
    }

    $data = [
      'site' => $bento_site_key,
      'type' => $name,
      'email' => $order->get_billing_email(),
      'details' => $details,
    ];

    $response = wp_remote_post($this->apiUrl . '/tracking/generic', [
      'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
      'body' => json_encode($data),
      'method' => 'POST',
      'data_format' => 'body',
    ]);
  }

  private function setEventValue($name, $details, $order)
  {
    $amount = 0;

    if ($name === '$OrderPlaced') {
      $amount = $order->get_total();
    } elseif ($name === '$OrderCancelled') {
      $amount = "-{$order->get_total()}";
    } elseif ($name === '$OrderRefunded') {
      $amount = "-{$order->get_total_refunded()}";
    }

    $details['value'] = [
      'amount' => $amount,
      'currency' => $order->get_currency(),
    ];

    $details['unique'] = [
      'key' => $order->get_order_key(),
    ];

    return $details;
  }

  private function getOrderItems($order)
  {
    $base_currency = get_woocommerce_currency();

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

    return $items;
  }
}

new Bento_Helper_Orders();
