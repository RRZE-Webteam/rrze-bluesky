<?php

namespace RRZE\Bluesky\Block;

defined('ABSPATH') || exit;

/**
 * Registers the Bluesky Gutenberg block and its assets.
 */
class Blocks
{
  public function __construct()
  {
    add_action('init', [$this, 'blockInit']);
    add_action('wp_enqueue_scripts', [$this, 'registerStyle']);
    add_filter('block_categories_all', [$this, 'addBlockCategory'], 10, 2);
  }

  /**
   * Initializes the block registration and sets up localization.
   *
   * @return void
   */
  public function blockInit(): void
  {
    $this->registerBlocksAndTranslations();
  }

  /**
   * Register the block styles for the frontend.
   *
   * @return void
   */
  public function registerStyle(): void
  {
    wp_register_style(
      'rrze-bluesky',
      plugins_url('css/rrze-bluesky.css', __DIR__),
      [],
      filemtime(plugin_dir_path(__DIR__) . 'css/rrze-bluesky.css')
    );
  }

  /**
   * Registers blocks and localizations.
   *
   * @return void
   */
  private function registerBlocksAndTranslations(): void
  {
    register_block_type(dirname(__DIR__, 2)  . '/build/bluesky', [
      'render_callback' => [Render::class, 'renderBlock'],
    ]);

    $script_handle = generate_block_asset_handle('rrze-bluesky/bluesky', 'editorScript');
    wp_set_script_translations($script_handle, 'rrze-bluesky', dirname(__DIR__, 2) . '/languages');
    load_plugin_textdomain('rrze-bluesky', false, dirname(plugin_basename(__DIR__)) . '/languages');
  }

  /**
   * Adds custom block category if not already present.
   *
   * @param array $categories Existing block categories.
   * @param WP_Post $post Current post object.
   * @return array Modified block categories.
   */
  public function addBlockCategory(array $categories, $post): array
  {
    // Check if there is already a RRZE category present
    foreach ($categories as $category) {
      if (isset($category['slug']) && $category['slug'] === 'rrze') {
        // If already present, skip registration
        return $categories;
      }
    }

    $categories[] = [
      'slug' => 'rrze',
      'title' => __('RRZE', 'rrze-bluesky'),
    ];

    return $categories;

  }
}
