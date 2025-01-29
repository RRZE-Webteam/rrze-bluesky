<?php

namespace RRZE\Bluesky;

use RRZE\Bluesky\Render;

defined('ABSPATH') || exit;

class Blocks
{
    public function __construct()
    {
        add_action('init', [$this, 'rrze_rrze_bluesky_block_init']);
        add_action( 'wp_enqueue_scripts', [$this, 'rrze_register_style']);
        add_filter('block_categories', [$this, 'my_custom_block_category'], 10, 2);
    }

    /**
     * Initializes the block registration and sets up localization.
     */
    public function rrze_rrze_bluesky_block_init()
    {
        $this->rrze_register_blocks_and_translations();
    }

    /**
     * Register the block styles for the frontend.
     */
    public function rrze_register_style()
    {
        wp_register_style(
            'rrze-bluesky',
            plugins_url('css/rrze-bluesky.css', __DIR__),
            [],
            filemtime(plugin_dir_path(__DIR__) . 'css/rrze-bluesky.css')
        );

        wp_enqueue_style('rrze-bluesky');
        wp_enqueue_script('rrze-video-front-js');
        wp_enqueue_style('rrze-video-plyr');
    }

    /**
     * Registers blocks and localizations.
     */
    private function rrze_register_blocks_and_translations()
    {
        $blocks = [
            'bluesky',
        ];

        foreach ($blocks as $block) {
            register_block_type(plugin_dir_path(__DIR__) . 'build/' . $block, [
                'render_callback' => [Render::class, 'renderBlock'],
            ]);

            load_plugin_textdomain('rrze-bluesky', false, dirname(plugin_basename(__DIR__)) . 'languages');

            $script_handle = generate_block_asset_handle('rrze-bluesky/' . $block, 'editorScript');
            wp_set_script_translations($script_handle, 'rrze-bluesky', plugin_dir_path(__DIR__) . 'languages');
        }

        // Register global styles and scripts here.
        // wp_enqueue_style('rrze-bluesky');
    }

    /**
     * Adds custom block category if not already present.
     *
     * @param array   $categories Existing block categories.
     * @param WP_Post $post       Current post object.
     * @return array Modified block categories.
     */
    public function my_custom_block_category($categories, $post)
    {
        // Check if there is already a RRZE category present
        foreach ($categories as $category) {
            if (isset($category['slug']) && $category['slug'] === 'rrze') {
                // Wenn bereits vorhanden, nichts ändern
                return $categories;
            }
        }

        $custom_category = [
            'slug'  => 'rrze',
            'title' => __('RRZE', 'rrze-bluesky'),
        ];

        // Add RRZE to the end of the categories array
        $categories[] = $custom_category;

        return $categories;
    }
}
