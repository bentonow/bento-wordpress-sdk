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

    if (!empty($bento_site_key)) {
      $test = var_dump($bento_site_key);
      wp_enqueue_script(
        'bento-js',
        "https://app.bentonow.com/{$bento_site_key}.js",
        [],
        false,
        true
      );

      /*
       * Enqueue scripts.
       */
      wp_enqueue_script(
        'bento-wordpress-sdk-js',
        plugins_url('assets/js/bento-wordpress-sdk.min.js', dirname(__FILE__)),
        ['jquery', 'bento-js'],
        $this->version,
        true
      );
    }
  }
}

new Bento_Custom();
