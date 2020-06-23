<?php

/**
 * This class loads Bento AJAX Endpoint.
 */
class Bento_Ajax
{
  /**
   * Constructor.
   */
  public function __construct()
  {
    add_action('wp_ajax_bento_get_cart_items', [$this, 'bento_get_cart_items']);

    add_action('wp_ajax_nopriv_bento_get_cart_items', [
      $this,
      'bento_get_cart_items',
    ]);
  }

  /**
   * Check any prerequisites required for our add to cart request.
   * From https://barn2.co.uk/managing-cart-rest-api-woocommerce-3-6/.
   */
  private function check_prerequisites()
  {
    if (defined('WC_ABSPATH')) {
      // WC 3.6+ - Cart and notice functions are not included during a REST request.
      include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
      include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
    }

    if (null === WC()->session) {
      $session_class = apply_filters(
        'woocommerce_session_handler',
        'WC_Session_Handler'
      );

      //Prefix session class with global namespace if not already namespaced
      if (false === strpos($session_class, '\\')) {
        $session_class = '\\' . $session_class;
      }

      WC()->session = new $session_class();
      WC()->session->init();
    }

    if (null === WC()->customer) {
      WC()->customer = new \WC_Customer(get_current_user_id(), true);
    }

    if (null === WC()->cart) {
      WC()->cart = new \WC_Cart();

      // We need to force a refresh of the cart contents from session here (cart contents are normally refreshed on wp_loaded, which has already happened by this point).
      WC()->cart->get_cart();
    }
  }

  public function bento_get_cart_items()
  {
    // cart start
    $this->check_prerequisites();

    // no session? start so cart/notices work
    if (!WC()->session || (WC()->session && !WC()->session->has_session())) {
      WC()->session->set_customer_session_cookie(true);
    }

    if (WC()->cart && !WC()->cart->is_empty()) {
      $cart = WC()->cart->get_cart();
      $products = $this->buildProductsArrayFromCart($cart);
      echo json_encode($products);
    } else {
      echo json_encode([]);
    }

    wp_die();
  }

  private function buildProductsArrayFromCart($cart)
  {
    $base_currency = get_woocommerce_currency();
    $products = array_values($cart);

    foreach ($products as &$product) {
      $product['product_name'] = $product['data']->get_name();
      $product['product_permalink'] = $product['data']->get_permalink();
      $product['product_price'] = $product['data']->get_price();
      $product['product_regular_price'] = $product['data']->get_regular_price();
      $product['product_sale_price'] = $product['data']->get_sale_price();
      $product['shop_base_currency'] = $base_currency;
    }
    unset($product);

    return $products;
  }
}

new Bento_Ajax();
