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
    wp_enqueue_script(
      'bento-js',
      'https://app.bentonow.com/6a7469acb729820021d3d8965da9fc85.js',
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

new Bento_Custom();
