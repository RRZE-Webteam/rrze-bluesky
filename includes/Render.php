<?php

namespace RRZE\Bluesky;

use RRZE\Bluesky\Helper;

class Render
{
    public function __construct()
    {
        // You can perform any initialization here if needed

        // Enqueue the block's stylesheet
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueueStyle']);
    }

    public static function enqueueStyle()
    {
        wp_enqueue_style('rrze-bluesky');
    }

    /**
     * Main entry point for block rendering.
     * @param array $args Block attributes.
     * @return string HTML to display on the frontend.
     */
    public static function renderBlock($args = [
        'publicTimeline' => false,
        'uri'            => 'https://bsky.app/profile/knoebel.bsky.social/post/3lfufbojaus24',
        'limit'          => 10,
    ])
    {
        Helper::debug("run");
        // Create an instance so we can call non-static methods.
        $renderer = new self();

        $isPublicTimeline = !empty($args['publicTimeline']);
        $uri             = isset($args['uri']) ? trim($args['uri']) : '';
        $uri =  'https://bsky.app/profile/der-postillon.com/post/3lg6e3d3f7u2s';
        $limit           = isset($args['limit']) ? (int) $args['limit'] : 10;

        // If publicTimeline is set, show timeline
        if ($isPublicTimeline) {
            $feedData = $renderer->retrievePublicTimelineInformation($limit);
            return $renderer->renderPublicTimeline($feedData);
        }

        // Otherwise, if we have a valid post URI, show the single post
        if (!empty($uri)) {
            $postData = $renderer->retrievePostInformation($uri);
            return $renderer->renderPost($postData);
        }

        // Fallback if neither condition is met
        return '<p>No data to display.</p>';
    }

    /**
     * Fetch data for a single Bluesky post (placeholder implementation).
     * @param string $uri The Bluesky post URI.
     * @return array|null Post data array or null if failed.
     */
    public function retrievePostInformation($uri)
    {
        // Placeholder for an actual API call or a call to your WP REST endpoint
        // Example:
        $response = wp_remote_get(home_url('wp-json/rrze-bluesky/v1/post?uri=' . urlencode($uri)));
        Helper::debug("Post data response:");
        Helper::debug($response);
        if (is_wp_error($response)) {
            return null;
        }
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        Helper::debug("Post data hereon:");
        Helper::debug($decoded);

        return $decoded;
    }

    /**
     * Render a single Bluesky post using the $postData array.
     * Mirrors the structure and classes from Post.tsx.
     *
     * @param array|null $postData The post data array.
     * @return string Generated HTML string.
     */
    public function renderPost($postData)
    {
        if (!$postData || !is_array($postData)) {
            return '<p>No post data found.</p>';
        }

        // Extract needed fields safely
        $author    = isset($postData['author']) && is_array($postData['author']) ? $postData['author'] : [];
        $record    = isset($postData['record']) && is_array($postData['record']) ? $postData['record'] : [];
        $embed     = isset($postData['embed'])  && is_array($postData['embed'])  ? $postData['embed']  : [];
        $uri       = isset($postData['uri']) ? $postData['uri'] : '';
        $likeCount = isset($postData['likeCount']) ? (int)$postData['likeCount'] : 0;
        $replyCount = isset($postData['replyCount']) ? (int)$postData['replyCount'] : 0;
        $repostCount = isset($postData['repostCount']) ? (int)$postData['repostCount'] : 0;

        // Author fields
        $displayName = isset($author['displayName']) ? $author['displayName'] : '';
        $handle      = isset($author['handle'])      ? $author['handle']      : '';
        $avatar      = isset($author['avatar'])      ? $author['avatar']      : '';
        $createdAt   = isset($author['createdAt'])   ? $author['createdAt']   : '';

        // Post text
        $postText = isset($record['text']) ? $record['text'] : '';

        // Check if this is a video embed
        $isVideoEmbed = (isset($embed['$type']) && $embed['$type'] === 'app.bsky.embed.video#view');

        // Start building HTML
        $html  = '<div class="wp-block-rrze-bluesky-bluesky"><article class="bsky-post">';
        $html .= '  <header>';
        $html .= '    <div class="author-information">';
        $html .= '      <a href="' . esc_url($this->getProfileUrl($handle)) . '">';
        $html .= '        <img src="' . esc_url($avatar) . '" alt="' . esc_attr($displayName) . '" />';
        $html .= '      </a>';
        $html .= '      <div class="author-name">';
        $html .= '        <h3><a href="' . esc_url($this->getProfileUrl($handle)) . '">' . esc_html($displayName) . '</a></h3>';
        $html .= '        <p><a href="' . esc_url($this->getProfileUrl($handle)) . '">@' . esc_html($handle) . '</a></p>';
        $html .= '      </div>';
        $html .= '    </div>';

        $html .= '    <div class="bsky-branding">';
        $html .= '      <a href="' . esc_url($this->getProfileUrl($handle)) . '">';
        // Inline SVG for Bluesky branding
        $html .= '        <svg class="bsky-logo" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 568 501">';
        $html .= '          <title>Bluesky butterfly logo</title>';
        $html .= '          <path fill="currentColor" d="M123.121 33.664C188.241 82.553 258.281 181.68 284 234.873c25.719-53.192 95.759-152.32 160.879-201.21C491.866-1.611 568-28.906 568 57.947c0 17.346-9.945 145.713-15.778 166.555-20.275 72.453-94.155 90.933-159.875 79.748C507.222 323.8 536.444 388.56 473.333 453.32c-119.86 122.992-172.272-30.859-185.702-70.281-2.462-7.227-3.614-10.608-3.631-7.733-.017-2.875-1.169.506-3.631 7.733-13.43 39.422-65.842 193.273-185.702 70.281-63.111-64.76-33.89-129.52 80.986-149.071-65.72 11.185-139.6-7.295-159.875-79.748C9.945 203.659 0 75.291 0 57.946 0-28.906 76.135-1.612 123.121 33.664Z"/>';
        $html .= '        </svg>';
        $html .= '      </a>';
        $html .= '    </div>';
        $html .= '  </header>';

        // Main Post Content
        $html .= '  <section class="bsky-post-content">';
        $html .= '    <p>' . esc_html($postText) . '</p>';

        // Check if embed has images
        if (!empty($embed['images']) && is_array($embed['images'])) {
            $html .= '<div class="bsky-image-gallery">';
            foreach ($embed['images'] as $img) {
                $thumb = isset($img['thumb']) ? $img['thumb'] : '';
                $alt   = isset($img['alt']) ? $img['alt'] : 'Bluesky embedded image';
                $html .= '<figure>';
                if (!empty($thumb)) {
                    $html .= '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($alt) . '"/>';
                } else {
                    $html .= '<p>[No image URL]</p>';
                }
                $html .= '</figure>';
            }
            $html .= '</div>';
        }
        // Check if embed->external
        elseif (!empty($embed['external']) && is_array($embed['external'])) {
            $ext  = $embed['external'];
            $extThumb   = isset($ext['thumb']) ? $ext['thumb'] : '';
            $extTitle   = isset($ext['title']) ? $ext['title'] : '';
            $extDesc    = isset($ext['description']) ? $ext['description'] : '';
            $extUri     = isset($ext['uri']) ? $ext['uri'] : '';

            // We'll show an <figure> with optional image, clickable title, and description
            $html .= '<figure class="bsky-external-embed">';

            if ($extThumb) {
                // $html .= '<a href="' . esc_url($extUri) . '" target="_blank" rel="noopener noreferrer">';
                $html .= '<img src="' . esc_url($extThumb) . '" alt="' . esc_attr($extTitle) . '">';
                // $html .= '</a>';
            }

            // figcaption with link to external article
            $html .= '<figcaption onclick="location.href=\'' . esc_url($extUri) . '\';" class="bsky-embed-caption" aria-label="' . esc_attr($extTitle) . '">';
            if (!empty($extUri)) {
                $html .= '<h4 class="bsky-external-heading"><a href="' . esc_url($extUri) . '" target="_blank" rel="noopener noreferrer">'
                    . esc_html($extTitle) . '</a></h4>';
            } else {
                $html .= '<h4 class="bsky-external-heading">' . esc_html($extTitle) . '</h4>';
            }
            // description
            if (!empty($extDesc)) {
                $html .= '<p  class="bsky-external-teaser">' . esc_html($extDesc) . '</p>';
            }
            $html .= '</figcaption>';
            $html .= '</figure>';
        }

        // If it's a video embed
        if ($isVideoEmbed) {
            // Typically, you might have something like "playlist" or "thumbnail" in the embed
            $mediaUrl = isset($embed['playlist'])  ? $embed['playlist']  : '';
            $poster   = isset($embed['thumbnail']) ? $embed['thumbnail'] : '';

            $html .= '<div class="bsky-video">';
            // Render the Vidstack or your custom video player:
            $html .= $this->renderVidstackVideo($mediaUrl, $poster);
            $html .= '</div>';
        }

        $html .= '  </section>';

        // Footer with post stats
        $html .= '  <footer>';
        $html .= '    <div class="publication-time">';

        if (!empty($createdAt)) {
            // Format date/time to match "DD. Mon YYYY um HH:MM" (de-DE style)
            $ts   = strtotime($createdAt);
            $date = date_i18n('d. M Y', $ts);
            $time = date_i18n('H:i', $ts);
            // If you want "um" to be translatable, you can wrap it in __()
            $html .= '<time datetime="' . esc_attr($createdAt) . '">' . esc_html($date) . ' ' . esc_html__('um', 'rrze-bluesky') . ' ' . esc_html($time) . '</time>';
        }

        $html .= '    </div>';
        $html .= '    <hr />';
        $html .= '    <div class="bsky-stat-section">';
        $html .= '      <div class="bsky-stat-icons">';

        // Like info
        $html .= '        <div class="bsky-like-info">';
        $html .= '          <svg class="bsky-like-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">';
        $html .= '            <path fill="currentColor" d="M47.6 300.4L228.3 469.1c7.5 7 17.4 10.9 27.7 10.9s20.2-3.9 27.7-10.9L464.4 300.4c30.4-28.3 47.6-68 47.6-109.5v-5.8c0-69.9-50.5-129.5-119.4-141C347 36.5 300.6 51.4 268 84L256 96 244 84c-32.6-32.6-79-47.5-124.6-39.9C50.5 55.6 0 115.2 0 185.1v5.8c0 41.5 17.2 81.2 47.6 109.5z" />';
        $html .= '          </svg> ' . (int)$likeCount;
        $html .= '        </div>';

        // Retweet / Repost info
        $html .= '        <div class="bsky-retweet-info">';
        $html .= '          <svg class="bsky-retweet-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">';
        $html .= '            <path fill="currentColor" d="M272 416c17.7 0 32-14.3 32-32s-14.3-32-32-32l-112 0c-17.7 0-32-14.3-32-32l0-128 32 0c12.9 0 24.6-7.8 29.6-19.8s2.2-25.7-6.9-34.9l-64-64c-12.5-12.5-32.8-12.5-45.3 0l-64 64c-9.2 9.2-11.9 22.9-6.9 34.9s16.6 19.8 29.6 19.8l32 0 0 128c0 53 43 96 96 96l112 0zM304 96c-17.7 0-32 14.3-32 32s14.3 32 32 32l112 0c17.7 0 32 14.3 32 32l0 128-32 0c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l64 64c12.5 12.5 32.8 12.5 45.3 0l64-64c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8l-32 0 0-128c0-53-43-96-96-96L304 96z" />';
        $html .= '          </svg> ' . (int)$repostCount;
        $html .= '        </div>';

        // Comment / Reply
        $html .= '        <div class="bsky-comment-info">';
        $html .= '          <a href="' . esc_url($this->getPostUrl($handle, $uri)) . '">';
        $html .= '            <svg class="bsky-reply-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">';
        $html .= '              <path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64L0 352c0 35.3 28.7 64 64 64l96 0 0 80c0 6.1 3.4 11.6 8.8 14.3s11.9 2.1 16.8-1.5L309.3 416 448 416c35.3 0 64-28.7 64-64l0-288c0-35.3-28.7-64-64-64L64 0z" />';
        $html .= '            </svg>';
        $html .= '          </a>';
        $html .= '        </div>';

        $html .= '      </div>';

        // "Read X replies on Bluesky"
        $html .= '      <div class="bsky-reply">';
        $html .= '        <a href="' . esc_url($this->getPostUrl($handle, $uri)) . '" class="bsky-reply-count">';
        $html .= '          ' . esc_html__('Read', 'rrze-bluesky') . ' ' . (int)$replyCount . ' ' . esc_html__('replies on Bluesky', 'rrze-bluesky');
        $html .= '        </a>';
        $html .= '      </div>';

        $html .= '    </div>';
        $html .= '  </footer>';
        $html .= '</article></div>';

        return $html;
    }

    /**
     * Fetch data for the public timeline (placeholder implementation).
     * @param int $limit Number of posts to retrieve.
     * @return array|null Feed data.
     */
    public function retrievePublicTimelineInformation($limit = 10)
    {
        //TODO: Fix the function
        $response = wp_remote_get(home_url('wp-json/rrze-bluesky/v1/public-timeline?limit=' . $limit));
        if (is_wp_error($response)) {
            return null;
        }
        $body   = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        return $decoded;
    }

    /**
     * Render the public timeline, looping through feed data.
     * Mirrors the structure and classes from PublicTimeline.tsx.
     *
     * @param array|null $feedData The feed data array.
     * @return string Generated HTML string.
     */
    public function renderPublicTimeline($feedData)
    {
        //TODO: Rethink the function
        if (!$feedData || empty($feedData['feed'])) {
            return '<p>No feed data available.</p>';
        }

        $html  = '<div class="bsky-public-timeline">';

        foreach ($feedData['feed'] as $index => $feedItem) {
            if (!isset($feedItem['post']) || !is_array($feedItem['post'])) {
                continue;
            }

            $postData = $feedItem['post'];
            $html .= $this->renderPost($postData);
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render a Vidstack video (or your custom video player).
     * Adjust the signature if you need more args like poster, etc.
     *
     * @param string $videoUrl URL to the video/playlist
     * @param string $poster   Poster/thumbnail image
     * @return string HTML for the video element/player
     */
    public function renderVidstackVideo($videoUrl, $poster = '')
    {
        //TODO: Fix the function... Rethink the Vidstack embedding
        $title       = 'Video Embed';
        $aspectRatio = '9/16';

        ob_start();
?>
        <div class="vidstack-player" style="--aspect-ratio: <?php echo esc_attr($aspectRatio); ?>;">
            <video
                src="<?php echo esc_url($videoUrl); ?>"
                poster="<?php echo esc_url($poster); ?>"
                controls>
                <?php echo esc_html($title); ?>
            </video>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Build a Bluesky profile link from the handle.
     * @param string $handle
     * @return string
     */
    protected function getProfileUrl($handle)
    {
        // e.g. "example.bsky.social" -> "https://bsky.app/profile/example.bsky.social"
        return 'https://bsky.app/profile/' . rawurlencode($handle);
    }

    /**
     * Build a Bluesky post link from the handle and post URI.
     * @param string $handle
     * @param string $postUri
     * @return string
     */
    protected function getPostUrl($handle, $postUri)
    {
        // last portion is typically the post's unique ID
        $parts  = explode('/', $postUri);
        $postId = end($parts);
        return 'https://bsky.app/profile/' . rawurlencode($handle) . '/post/' . rawurlencode($postId);
    }
}
