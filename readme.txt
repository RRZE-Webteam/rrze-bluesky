 === Plugin Name: RRZE Bluesky ===
  Version: 1.0.0
  Plugin URI: https://github.com/RRZE-Webteam/rrze-bluesky
  GitHub Issue URL: https://github.com/RRZE-Webteam/rrze-bluesky/issues
  Author: RRZE-Webteam <webmaster@fau.de>
  Author URI: https://www.wp.rrze.fau.de
  Licence: GNU General Public License v3
  Licence URI: http://www.gnu.org/licenses/gpl-3.0.html
  Requires at least: 5.8
  Tested up to: 7.0
  Requires PHP: 8.2
  Text Domain: rrze-bluesky
  Tags: bluesky, social media, embed, atproto, fediverse

  Embed Bluesky posts and profile data on your WordPress website in a
  GDPR-compliant way.

  == Description ==

  RRZE Bluesky lets you embed posts, timelines, and starter pack lists
  from Bluesky directly on your WordPress website. Authentication is
  handled server-side via the AT Protocol API — no external scripts are
  loaded in the frontend.

  **Features:**

  * Embed single Bluesky posts by URL or AT URI
  * Display your public Bluesky timeline
  * Render Bluesky Starter Pack member lists
  * Gutenberg block with editor preview
  * Server-side token management with automatic refresh
  * Encrypted credential storage
  * Caching via WordPress transients

  == Installation ==

  1. Upload the plugin folder to `/wp-content/plugins/`.
  2. Activate the plugin in the WordPress admin under **Plugins**.
  3. Go to **Settings → RRZE Bluesky** and enter your Bluesky handle and
  an app password.
  4. Use the **RRZE Bluesky** block in the block editor.

  == Frequently Asked Questions ==

  = Which password do I need to enter? =

  Do not use your main Bluesky password. Create an **app password** under
  bsky.app → Settings → Privacy & Security → App Passwords.

  = Is this GDPR-compliant? =

  Yes. All API requests are made server-side. No Bluesky scripts or
  tracking pixels are loaded in visitors' browsers.

  = Does this work without a Bluesky account? =

  No. A Bluesky account with an app password is required for
  authentication against the AT Protocol API.

  == Changelog ==

  = 1.0.0 =
  * Initial release

  == Upgrade Notice ==

  = 1.0.0 =
  Initial release.
