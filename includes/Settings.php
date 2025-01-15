<?php

namespace RRZE\Bluesky;

defined('ABSPATH') || exit;

class Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'initializeSettings']);
    }

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

    public function renderSettingsPage()
    {
        ?>
        <div class="wrap">
            <h1><?php echo get_admin_page_title(); ?></h1>
            <p class="about-text"><?php _e("Settings for RRZE Bluesky Plugin.", "rrze-bluesky"); ?></p>

            <hr />
            
            <!-- Form to enter the API token -->
            <form action="options.php" method="post">
                <?php
                settings_fields('rrze-bluesky-settings-group');
                do_settings_sections('rrze-bluesky');
                submit_button();
                ?>
            </form>
            
        </div>
        <?php
    }

    public function initializeSettings()
    {
        $this->addOption();
        $this->registerSettings();
    }

    public function addOption()
    {
        if (!get_option('rrze_bluesky_password')) {
            add_option('rrze_bluesky_password', '', '', 'yes');
        }

        if (!get_option('rrze_bluesky_username')) {
            add_option('rrze_bluesky_username', '', '', 'yes');
        }
    }

    public function registerSettings()
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
                'sanitize_callback' => [$this, 'sanitizeApiKey'],
                'default' => null,
            ]
        );

        add_settings_section(
            'rrze-bluesky-settings-section',
            'Bluesky Password',
            [$this, 'settingsSectionCallback'],
            'rrze-bluesky'
        );

        add_settings_field(
            'rrze-bluesky-username-field',
            'Bluesky Username',
            [$this, 'apiUsernameFieldCallback'],
            'rrze-bluesky',
            'rrze-bluesky-settings-section'
        );

        add_settings_field(
            'rrze-bluesky-password-field',
            'Bluesky Password',
            [$this, 'apiPasswordFieldCallback'],
            'rrze-bluesky',
            'rrze-bluesky-settings-section'
        );
    }

    public function settingsSectionCallback()
    {
        echo 'Enter your API token below:';
    }

    public function apiUsernameFieldCallback()
    {
        $data_encryption = new Encryption();
        $decrypted_username = $data_encryption->decrypt(get_option('rrze_bluesky_username'));
        echo "<input type='text' name='rrze_bluesky_username' value='" . esc_attr($decrypted_username) . "' />";
    }

    public function apiPasswordFieldCallback()
    {
        $token = get_option('rrze_bluesky_password');
        $displayValue = !empty($token) ? str_repeat('*', 48) : ''; 
        echo "<input type='text' name='rrze_bluesky_password' value='" . esc_attr($displayValue) . "' />";
    }

    public function sanitizeApiKey($input)
    {
        $data_encryption = new Encryption();
        $encrypted_password = get_option('rrze_bluesky_password');

        // If input is a masked value, return the existing token
        if ($input === str_repeat('*', 48)) {
            return $encrypted_password;
        }

        // Sanitize and encrypt the new input value
        $input = sanitize_text_field($input);
        return $data_encryption->encrypt($input);
    }
}