<?php

/**
 * Custom changes that Bento implements
 */
class Bento_Custom
{
  /**
   * Current version of Bento.
   */
  public $version = '0.1.0';

  public function __construct()
  {
    add_action('wp_enqueue_scripts', [$this, 'scripts_styles']);
  }

  /**
   * Scripts for Bento's custom source tracking and cart tracking.
   */
  public function scripts_styles()
  {
    $bento_options = get_option('bento_settings');
    $bento_site_key = $bento_options['bento_site_key'];

    if (empty($bento_site_key)) {
      return;
    }

    wp_enqueue_script(
      'bento-js',
      "https://app.bentonow.com/{$bento_site_key}.js?woocommerce=1",
      [],
      false,
      true
    );

    $params = $this->getBentoWordpressSDKJSParams();

    wp_localize_script('bento-js', 'bento_wordpress_sdk_params', $params);
  }

  private function getBentoWordpressSDKJSParams()
  {
    /**
     * Pass parameters to Bento Wordpress SDK.
     */
    $params = [
      'ajax_url' => admin_url('admin-ajax.php'),
    ];

    $current_user = wp_get_current_user();
    if (0 == $current_user->ID) {
      $params['user_logged_in'] = false;
    } else {
      $params['user_logged_in'] = true;
      $params['user_email'] = $current_user->user_email;
    }

    $params['woocommerce_enabled'] = class_exists('WooCommerce');

    if ($params['woocommerce_enabled']) {
      $params['woocommerce_cart_count'] = WC()->cart
        ? WC()->cart->get_cart_contents_count()
        : 0;
    }

    return $params;
  }
}

new Bento_Custom();
