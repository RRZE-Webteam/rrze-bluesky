<?php

namespace RRZE\Bluesky;

defined('ABSPATH') || exit;

class Settings
{
    private $api;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'initializeSettings']);
    }

    /**
     * Create a submenu item under "Settings" in WP Admin.
     */
    public function addSettingsPage()
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
     */
    public function renderSettingsPage()
    {
        ?>
                <div class="wrap">
                    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                    <p class="about-text"><?php esc_html_e("Settings for RRZE Bluesky Plugin.", "rrze-bluesky"); ?></p>
                    <hr />

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
                            value="<?php esc_attr_e('Reset Credentials', 'rrze-bluesky'); ?>"
                            
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
     * Example method to demonstrate a profile fetch with the saved username/password.
     * Displays the user’s Bluesky profile below the settings form if valid credentials.
     */
    public function printExampleApiOutput($encryptedUsername)
    {
        // Decrypt the stored handle:
        $data_encryption   = new Encryption();
        $decryptedUsername = $data_encryption->decrypt($encryptedUsername);

        // Decrypt the password from options as well:
        $encryptedPassword = get_option('rrze_bluesky_password');
        $decryptedPassword = $data_encryption->decrypt($encryptedPassword);

        if (empty($decryptedUsername) || empty($decryptedPassword)) {
            echo '<p>Bluesky username/password not set.</p>';
            return;
        }

        // Instantiate API with the user’s credentials:
        $api = new API($decryptedUsername, $decryptedPassword);

        // Create a new Render instance and ensure it uses the same API instance:
        $renderer = new Render();
        $renderer->setApi($api);

        // Render the personal profile card:
        echo '<h2>' . __("Currently connected Bluesky-User:", "rrze-bluesky") . '</h2>';
        echo $renderer->renderPersonalProfile($decryptedUsername);
    }

    /**
     * Initialize the settings (run once on admin_init).
     */
    public function initializeSettings()
    {
        $this->addOption();
        $this->registerSettings();
        $this->handleResetCredentials();
    }

    /**
     * Ensure our plugin options exist in the DB.
     */
    public function addOption()
    {
        if (!get_option('rrze_bluesky_password')) {
            add_option('rrze_bluesky_password', '', '', 'yes');
        }

        if (!get_option('rrze_bluesky_username')) {
            add_option('rrze_bluesky_username', '', '', 'yes');
        }
    }

    /**
     * Register settings, sections, and fields with WP’s Settings API.
     */
    public function registerSettings()
    {
        register_setting(
            'rrze-bluesky-settings-group',
            'rrze_bluesky_username',
            [
                'type'              => 'string',
                'sanitize_callback' => [$this, 'sanitizeApiKey'],
                'default'           => null,
            ]
        );

        register_setting(
            'rrze-bluesky-settings-group',
            'rrze_bluesky_password',
            [
                'type'              => 'string',
                'sanitize_callback' => [$this, 'sanitizeApiKey'],
                'default'           => null,
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
     */
    public function settingsSectionCallback()
    {
        echo '<p>' . esc_html__('Enter your Bluesky handle (username) and password to authenticate.', 'rrze-bluesky') . '</p>';
    }

    /**
     * Username (handle) field callback.
     */
    public function apiUsernameFieldCallback()
    {
        $data_encryption   = new Encryption();
        $decryptedUsername = $data_encryption->decrypt(get_option('rrze_bluesky_username'));

        echo '<input type="text" name="rrze_bluesky_username" value="' . esc_attr($decryptedUsername) . '" />';
    }

    /**
     * Password field callback (mask if stored).
     */
    public function apiPasswordFieldCallback()
    {
        $token        = get_option('rrze_bluesky_password') || '';
        $displayValue = (!empty($token) || '') ? str_repeat('*', 8) : '';

        echo '<input type="text" name="rrze_bluesky_password" value="' . esc_attr($displayValue) . '" />';
    }

    /**
     * Sanitize & encrypt the API key/username/password before saving to DB.
     */
    public function sanitizeApiKey($input)
    {
        $data_encryption = new Encryption();

        // Basic string sanitization
        $clean = sanitize_text_field($input);

        // Encrypt the clean value
        return $data_encryption->encrypt($clean);
    }


    /**
     * Check if the reset button has been pressed and, if so, clear credentials and transients.
     */
    public function handleResetCredentials()
    {
        if (!isset($_POST['rrze_bluesky_reset'])) {
            return;
        }

        check_admin_referer('rrze_bluesky_reset_action', 'rrze_bluesky_reset_nonce');

        update_option('rrze_bluesky_username', '');
        update_option('rrze_bluesky_password', '');

        unset($_POST['rrze_bluesky_username']);
        unset($_POST['rrze_bluesky_password']);

        global $wpdb;
        $like_pattern    = $wpdb->esc_like('_transient_rrze_bluesky_') . '%';
        $timeout_pattern = $wpdb->esc_like('_transient_timeout_rrze_bluesky_') . '%';

        $transient_names = $wpdb->get_col("
            SELECT option_name
            FROM $wpdb->options
            WHERE option_name LIKE '$like_pattern'
            OR option_name LIKE '$timeout_pattern'
        ");

        foreach ($transient_names as $option_name) {
            $transient = str_replace('_transient_', '', $option_name);
            delete_transient($transient);
        }

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__('Bluesky credentials reset successfully.', 'rrze-bluesky')
                . '</p></div>';
        });
    }
}
