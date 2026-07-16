<?php

namespace RRZE\Bluesky;

defined('ABSPATH') || exit;

use RRZE\Bluesky\Admin\Settings;
use RRZE\Bluesky\Block\Blocks;
use RRZE\Bluesky\REST\REST;

/**
 * Plugin bootstrap. Instantiates all required service classes.
 */
class Main
{
  /**
   * Initialises all service classes and runs update routines.
   */
  public function __construct()
  {
    $this->maybeClearTransientsAfterUpdate();

    new Helper();
    new Settings();
    new Blocks();
    new REST();
  }

  /**
   * Clears plugin transients when the stored version does not match the current version.
   *
   * Runs once after an update to flush any cached data that may contain
   * serialized objects from renamed or restructured classes.
   *
   * @return void
   */
  private function maybeClearTransientsAfterUpdate(): void
  {
    $storedVersion  = get_option('rrze_bluesky_version', '');
    $currentVersion = '1.1.0';

    if ($storedVersion === $currentVersion) {
      return;
    }

    // Clear transients that may contain serialized objects
    // from old class names or outdated data structures.
    global $wpdb;
    $wpdb->query(
      "DELETE FROM {$wpdb->options}
           WHERE option_name LIKE '_transient_rrze_bluesky_starterpack_all_%'
           OR option_name LIKE '_transient_rrze_bluesky_get_list_%'
           OR option_name LIKE '_transient_rrze_bluesky_post_%'
           OR option_name LIKE '_transient_timeout_rrze_bluesky_starterpack_all_%'
           OR option_name LIKE '_transient_timeout_rrze_bluesky_get_list_%'
           OR option_name LIKE '_transient_timeout_rrze_bluesky_post_%'"
    );

    update_option('rrze_bluesky_version', $currentVersion);
  }


}
