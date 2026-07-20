<?php

namespace RRZE\Bluesky\Admin;

use RRZE\Bluesky\API\API;
use RRZE\Bluesky\Block\Render;
use RRZE\Bluesky\Encryption;

defined('ABSPATH') || exit;

/**
 * Registers and renders the RRZE Bluesky settings page in WP Admin.
 */
class Settings
{
  /**
   * Registers admin hooks for the settings page.
   */
  public function __construct()
  {
    add_action('admin_menu', [$this, 'addSettingsPage']);
    add_action('admin_init', [$this, 'initializeSettings']);
  }

  /**
   * Create a submenu item under "Settings" in WP Admin.
   *
   * @return void
   */
  public function addSettingsPage(): void
  {
    add_submenu_page(
      'options-general.php',
      __('RRZE Bluesky', 'rrze-bluesky'),
      __('RRZE Bluesky', 'rrze-bluesky'),
      'manage_options',
      'rrze-bluesky',
      [$this, 'renderSettingsPage']
    );
  }

  /**
   * Render the actual Settings page in WP Admin.
   *
   * @return void
   */
  public function renderSettingsPage(): void
  {
    if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
      echo '<div class="notice notice-success is-dismissible"><p>'
        . esc_html__('Bluesky credentials reset successfully.',
          'rrze-bluesky')
        . '</p></div>';
    }
    ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <p class="about-text"><?php esc_html_e("Settings for RRZE Bluesky Plugin.", "rrze-bluesky"); ?></p>
      <hr/>

      <!-- WP Settings Form -->
      <form action="options.php" method="post">
        <?php
        // Security fields for the registered setting:
        settings_fields('rrze-bluesky-settings-group');

        // Output the settings sections and fields
        do_settings_sections('rrze-bluesky');

        // Default WP "Save Changes" button
        submit_button(__('Save Changes', 'rrze-bluesky'));

        // Then our custom reset button:
        wp_nonce_field('rrze_bluesky_reset_action', 'rrze_bluesky_reset_nonce');
        ?>
        <input type="submit"
               name="rrze_bluesky_reset"
               class="button button-secondary"
               value="<?php esc_attr_e('Reset Credentials', 'rrze-bluesky'); ?>"/>
      </form>
    </div>
    <?php

    // After the form, try displaying the user’s profile if a handle is saved:
    $encryptedUsername = get_option('rrze_bluesky_username');
    if (!empty($encryptedUsername)) {
      $this->printExampleApiOutput($encryptedUsername);
    }
  }

  /**
   * Fetches and displays the connected Bluesky profile below the settings form.
   *
   * @param string $encryptedUsername The encrypted username from the database.
   * @return void
   */
  public function printExampleApiOutput(string $encryptedUsername): void
  {
    // Decrypt the stored handle:
    $data_encryption = new Encryption();
    $decryptedUsername = $data_encryption->decrypt($encryptedUsername);

    // Decrypt the password from options as well:
    $encryptedPassword = get_option('rrze_bluesky_password');
    $decryptedPassword = $data_encryption->decrypt($encryptedPassword);

    if (empty($decryptedUsername) || empty($decryptedPassword)) {
      echo '<p>' . esc_html__('Bluesky username/password not set.', 'rrze-bluesky') . '</p>';
      return;
    }

    try {
      // Instantiate API with the user’s credentials:
      $api = new API($decryptedUsername, $decryptedPassword);

      // Create a new Render instance and ensure it uses the same API instance:
      $renderer = new Render();
      $renderer->setApi($api);

      // Render the personal profile card:
      echo '<h2>' . esc_html__("Currently connected Bluesky-User:", "rrze-bluesky") . '</h2>';
      echo $renderer->renderPersonalProfile($decryptedUsername);
    } catch (\Exception $e) {
      echo '<p>' . esc_html__('Error fetching profile:', 'rrze-bluesky') . ' ' . esc_html($e->getMessage()) . '</p>';
    }
  }

  /**
   * Initialize the settings (run once on admin_init).
   *
   * @return void
   */
  public function initializeSettings(): void
  {
    $this->handleResetCredentials();
    $this->addOption();
    $this->registerSettings();
  }

  /**
   * Ensure plugin options exist in the database with default empty values.
   *
   * @return void
   */
  public function addOption(): void
  {
    if (!get_option('rrze_bluesky_password')) {
      add_option('rrze_bluesky_password', '', 'false');
    }

    if (!get_option('rrze_bluesky_username')) {
      add_option('rrze_bluesky_username', '', 'false');
    }
  }

  /**
   * Register settings, sections, and fields with WP’s Settings API.
   *
   * @return void
   */
  public function registerSettings(): void
  {
    register_setting(
      'rrze-bluesky-settings-group',
      'rrze_bluesky_username',
      [
        'type' => 'string',
        'sanitize_callback' => [$this, 'sanitizeApiKey'],
        'default' => null,
      ]
    );

    register_setting(
      'rrze-bluesky-settings-group',
      'rrze_bluesky_password',
      [
        'type' => 'string',
        'sanitize_callback' => [$this, 'sanitizePassword'],
        'default' => null,
      ]
    );

    add_settings_section(
      'rrze-bluesky-settings-section',
      __('Bluesky Credentials', 'rrze-bluesky'),
      [$this, 'settingsSectionCallback'],
      'rrze-bluesky'
    );

    add_settings_field(
      'rrze-bluesky-username-field',
      __('Bluesky Username', 'rrze-bluesky'),
      [$this, 'apiUsernameFieldCallback'],
      'rrze-bluesky',
      'rrze-bluesky-settings-section'
    );

    add_settings_field(
      'rrze-bluesky-password-field',
      __('Bluesky Password', 'rrze-bluesky'),
      [$this, 'apiPasswordFieldCallback'],
      'rrze-bluesky',
      'rrze-bluesky-settings-section'
    );
  }

  /**
   * Settings section intro text.
   *
   * @return void
   */
  public function settingsSectionCallback(): void
  {
    echo '<p>' . esc_html__('Enter your Bluesky handle (username) and password to authenticate.', 'rrze-bluesky') . '</p>';
  }

  /**
   * Username (handle) field callback.
   *
   * @return void
   */
  public function apiUsernameFieldCallback(): void
  {
    $data_encryption = new Encryption();
    $decryptedUsername = $data_encryption->decrypt(get_option('rrze_bluesky_username'));

    echo '<input type="text" name="rrze_bluesky_username" value="' . esc_attr($decryptedUsername) . '" />';
  }

  /**
   * Password field callback (mask if stored).
   *
   * @return void
   */
  public function apiPasswordFieldCallback(): void
  {
    $stored = get_option('rrze_bluesky_password') ?: '';
    $placeholder = !empty($stored)
      ? esc_attr__('Password is set — leave blank to keep', 'rrze-bluesky') : '';
    echo '<input type="password" name="rrze_bluesky_password" value="" placeholder="' . $placeholder . '" />';
  }


  /**
   * Sanitizes and encrypts a credential value before saving to the database.
   *
   * @param string $input The raw input value.
   * @return string The encrypted value, or an empty string if input is empty.
   */
  public function sanitizeApiKey(string $input): string
  {
    if ($input === null || $input === '') {
      return '';
    }
    $clean = sanitize_text_field($input);
    if ($clean === '') {
      return '';
    }
    $data_encryption = new Encryption();
    $encrypted = $data_encryption->encrypt($clean);
    return is_string($encrypted) ? $encrypted : '';
  }

  /**
   * Sanitizes and encrypts the password before saving.
   * If the input is empty, the existing stored value is kept.
   *
   * @param string|null $input The raw password input.
   * @return string The encrypted password, or the existing value if input is empty.
   */
  public function sanitizePassword(?string $input): string
  {
    if ($input === null || $input === '') {
      return get_option('rrze_bluesky_password', '');
      }
    $clean = sanitize_text_field($input);
    if ($clean === '') {
      return get_option('rrze_bluesky_password', '');
      }
    $data_encryption = new Encryption();
    $encrypted = $data_encryption->encrypt($clean);
    return is_string($encrypted) ? $encrypted : '';
  }


  /**
   * Handle the reset credentials form submission.
   * Deletes stored credentials and clears authentication transients.
   *
   * @return void
   */
  public function handleResetCredentials(): void
  {
    if (!isset($_POST['rrze_bluesky_reset'])) {
      return;
    }

    check_admin_referer('rrze_bluesky_reset_action', 'rrze_bluesky_reset_nonce');

    delete_option('rrze_bluesky_username');
    delete_option('rrze_bluesky_password');
    delete_option('rrze_bluesky_secret_key');

    delete_transient('rrze_bluesky_refresh_token');
    delete_transient('rrze_bluesky_access_token');

    wp_redirect(add_query_arg(
      ['page' => 'rrze-bluesky', 'reset' => 'success'],
      admin_url('options-general.php')
    ));
    exit;
  }
}
