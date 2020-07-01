<?php
class BentoSettingsPage
{
  /**
   * Holds the values to be used in the fields callbacks
   */
  private $options;

  /**
   * Start up
   */
  public function __construct()
  {
    add_action('admin_menu', [$this, 'add_plugin_page']);
    add_action('admin_init', [$this, 'page_init']);
  }

  /**
   * Add options page
   */
  public function add_plugin_page()
  {
    // This page will be under "Settings"
    add_menu_page(
      'Bento',
      'Bento',
      'manage_options',
      'bento-setting-admin',
      [$this, 'create_admin_page'],
      plugin_dir_url(__DIR__) . 'assets/img/bento-logo-colour.png'
    );
  }

  /**
   * Options page callback
   */
  public function create_admin_page()
  {
    // Set class property
    $this->options = get_option('bento_settings'); ?>
        <div class="wrap">
            <h1>Bento Settings</h1>
            <form method="post" action="options.php">
            <?php
            // This prints out all hidden setting fields
            settings_fields('bento_option_group');
            do_settings_sections('bento-setting-admin');
            submit_button();?>
            </form>
        </div>
        <?php
  }

  /**
   * Register and add settings
   */
  public function page_init()
  {
    register_setting(
      'bento_option_group', // Option group
      'bento_settings', // Option name
      [$this, 'sanitize'] // Sanitize
    );

    add_settings_section(
      'bento_setting_section_id', // ID
      'Configure Bento Site Key', // Title
      [$this, 'print_section_info'], // Callback
      'bento-setting-admin' // Page
    );

    add_settings_field(
      'bento_site_key', // ID
      'Site Key', // Title
      [$this, 'bento_site_key_callback'], // Callback
      'bento-setting-admin', // Page
      'bento_setting_section_id' // Section
    );
  }

  /**
   * Sanitize each setting field as needed
   *
   * @param array $input Contains all settings fields as array keys
   */
  public function sanitize($input)
  {
    $new_input = [];
    if (isset($input['bento_site_key'])) {
      $new_input['bento_site_key'] = sanitize_text_field(
        $input['bento_site_key']
      );
    }

    return $new_input;
  }

  /**
   * Print the Section text
   */
  public function print_section_info()
  {
    print 'You can find your site key in your account dashboard. If you have trouble finding it, just ask support.';
  }

  /**
   * Get the settings option array and print one of its values
   */
  public function bento_site_key_callback()
  {
    printf(
      '<input type="text" id="bento_site_key" name="bento_settings[bento_site_key]" value="%s" />',
      isset($this->options['bento_site_key'])
        ? esc_attr($this->options['bento_site_key'])
        : ''
    );
  }
}

if (is_admin()) {
  $bento_settings_page = new BentoSettingsPage();
}
