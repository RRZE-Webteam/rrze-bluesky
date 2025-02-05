<?php

/*
Plugin Name:    RRZE Bluesky
Plugin URI:     https://www.github.com/rrze-webteam/rrze-bluesky
Description:    A WordPress Plugin to embed information and posts from Bluesky on your website.
Version:        1.0.0
Author:         RRZE Webteam
Author URI:     https://blogs.fau.de/webworking/
License:        GNU General Public License v3
License URI:    http://www.gnu.org/licenses/gpl-3.0.html
Text Domain:    rrze-bluesky
*/

namespace RRZE\Bluesky;

defined('ABSPATH') || exit;

use RRZE\Bluesky\Main;

const RRZE_PHP_VERSION = '7.4';
const RRZE_WP_VERSION = '5.8';

/**
 * SPL Autoloader (PSR-4).
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__;
    $base_dir = __DIR__ . '/includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');

add_action('plugins_loaded', __NAMESPACE__ . '\loaded');
add_action('plugins_loaded', __NAMESPACE__ . '\loadTextDomain');

/**
 * System requirements verification.
 * @return string Return an error message.
 */
function systemRequirements(): string
{
    $error = '';
    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        $error = sprintf(
            /* translators: 1: Server PHP version number, 2: Required PHP version number. */
            __('The server is running PHP version %1$s. The Plugin requires at least PHP version %2$s.', 'rrze-bluesky'),
            PHP_VERSION,
            RRZE_PHP_VERSION
        );
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        $error = sprintf(
            /* translators: 1: Server WordPress version number, 2: Required WordPress version number. */
            __('The server is running WordPress version %1$s. The Plugin requires at least WordPress version %2$s.', 'rrze-bluesky'),
            $GLOBALS['wp_version'],
            RRZE_WP_VERSION
        );
    }
    return $error;
}

/**
 * Translation
 */
function loadTextDomain()
 {
    load_plugin_textdomain('rrze-bluesky', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Deactivation callback function
 */
function deactivation()
{
    delete_option('rrze_bluesky_username');
    delete_option('rrze_bluesky_password');
    delete_option('rrze_bluesky_secret_key');

    delete_transient('rrze_bluesky_refresh_token');
    delete_transient('rrze_bluesky_access_token');
}

/**
 * Instantiate Plugin class.
 * @return object Plugin
 */
function plugin()
{
    static $instance;
    if (null === $instance) {
        $instance = new Plugin(__FILE__);
    }
    return $instance;
}

/**
 * Loaded callback function.
 *
 * @return void
 */
function loaded()
{
    plugin()->loaded();
    if ($error = systemRequirements()) {
        add_action('admin_init', function () use ($error) {
            if (current_user_can('activate_plugins')) {
                $pluginData = get_plugin_data(plugin()->getFile());
                $pluginName = $pluginData['Name'];
                $tag = is_plugin_active_for_network(plugin()->getBaseName()) ? 'network_admin_notices' : 'admin_notices';
                add_action($tag, function () use ($pluginName, $error) {
                    printf(
                        '<div class="notice notice-error"><p>' .
                            /* translators: 1: The plugin name, 2: The error string. */
                            __('Plugins: %1$s: %2$s', 'rrze-bluesky') .
                            '</p></div>',
                        esc_html($pluginName),
                        esc_html($error)
                    );
                });
            }
        });
        return;
    }

    new Main;
}