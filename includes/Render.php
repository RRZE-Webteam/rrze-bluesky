<?php

namespace RRZE\Bluesky;

use RRZE\Bluesky\Api;

class Render
{
    private ?API $api = null;

    public function __construct()
    {
        $data_encryption = new Encryption();
        $username = $data_encryption->decrypt(get_option('rrze_bluesky_username'));
        $password = $data_encryption->decrypt(get_option('rrze_bluesky_password'));

        if (empty($username) || empty($password)) {
            return;
        }

        $api = new API($username, $password);
        $this->setApi($api);
    }

    /** 
     * Setter for API 
     */
    public function setApi(API $api): void
    {
        $this->api = $api;
    }

    /**
     * Getter for API
     */
    public function getApi()
    {
        return $this->api;
    }


    /**
     * Main entry point for block rendering.
     * @param array $args Block attributes.
     * @return string HTML to display on the frontend.
     */
    public static function renderBlock($args = [
        'publicTimeline' => false,
        'uri'            => '',
        'limit'          => 10,
        'isPost'         => false,
        'isStarterPack'  => false,
        'hstart'         => 2
    ]): string
    {
        $renderer = new self();

        $uri             = isset($args['postUrl']) ? trim($args['postUrl']) : '';
        $limit           = isset($args['limit']) ? (int) $args['limit'] : 10;
        $isPost          = isset($args['isPost']) ? (bool) $args['isPost'] : false;
        $isStarterPack   = isset($args['isStarterPack']) ? (bool) $args['isStarterPack'] : false;
        $isPublicTimeline = !empty($args['publicTimeline']);
        $hstart          = isset($args['hstart']) ? (int) $args['hstart'] : 2;

        // If publicTimeline is set, show timeline
        if ($isPublicTimeline) {
            $feedData = $renderer->retrievePublicTimelineInformation($limit);
            return $renderer->renderPublicTimeline($feedData);
        }

        if ($isStarterPack && !empty($uri)) {
            $api = $renderer->getApi();
            $listData = $api->getAllStarterPackData($uri);
            return $renderer->renderStarterpackList($listData, $hstart);
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
        $response = wp_remote_get(
            home_url('wp-json/rrze-bluesky/v1/post?uri=' . urlencode($uri)),
            [
                'headers' => [
                    'X-RRZE-Secret-Key' => get_option('rrze_bluesky_secret_key'),
                ],
            ]
        );

        if (is_wp_error($response)) {
            return null;
        }
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        return $decoded;
    }

    /**
     * Render Post Header
     */
    public function renderPostHeader($handle, $displayName, $avatar)
    {
        $html  = '  <header>';
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
        return $html;
    }

    /**
     * Render Main Post Content
     */
    public function renderPostContent($postText, $embed, $isVideoEmbed = false, $isRecordEmbed = false)
    {
        // Main Post Content
        $html  = '  <section class="bsky-post-content">';
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
            if (!empty($extUri)) {
                $html .= '<hr />';
                $parsedUrl = parse_url($extUri);
                $domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : $extUri;
                $html .= '<p class="bsky-external-domain-host">' . esc_html($domain) . '</p>';
            }
            $html .= '</figcaption>';
            $html .= '</figure>';
        }

        // If it's a video embed
        if ($isVideoEmbed) {
            // Typically, you might have something like "playlist" or "thumbnail" in the embed
            $mediaUrl = isset($embed['playlist'])  ? $embed['playlist']  : '';
            $poster   = isset($embed['thumbnail']) ? $embed['thumbnail'] : '';
            $videoCid = isset($embed['cid']) ? $embed['cid'] : '';
            $videoHeight = isset($embed['height']) ? $embed['height'] : '';
            $videoWidth = isset($embed['width']) ? $embed['width'] : '';
            $videoAspectRatioClass = $this->get_aspectratio_class($videoHeight, $videoWidth);

            $html .= '<div class="bsky-video">';
            // Render the Vidstack or your custom video player:
            $html .= $this->renderVidstackVideo($mediaUrl, $poster, $videoCid, $videoAspectRatioClass);
            $html .= '</div>';
        }

        if ($isRecordEmbed && !empty($embed['record'])) {
            $html .= '<div class="bsky-embedded-post">';
            $html .= $this->renderEmbeddedRecord($embed['record']);
            $html .= '</div>';
        }

        $html .= '  </section>';
        return $html;
    }

    /**
     * Render Post Footer
     */
    public function renderPostFooter($hideFooter, $handle, $uri, $createdAt, $likeCount, $repostCount, $replyCount)
    {
        if ($hideFooter) {
            return '';
        }

        // Footer with post stats
        $html  = '  <footer>';
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
        if (!$hideFooter) {
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
            if ($replyCount > 0) {
                $html .= '          ' . esc_html__('Read', 'rrze-bluesky') . ' ' . (int)$replyCount . ' ' . esc_html__('replies on Bluesky', 'rrze-bluesky');
            } else {
                $html .= '          ' . esc_html__('Read on Bluesky', 'rrze-bluesky');
            }
            $html .= '        </a>';
            $html .= '      </div>';

            $html .= '    </div>';
        }
        $html .= '  </footer>';
        return $html;
    }

    /**
     * Render a single Bluesky post using the $postData array.
     * Mirrors the structure and classes from Post.tsx.
     *
     * @param array|null $postData The post data array.
     * @return string Generated HTML string.
     */
    public function renderPost($postData, $hideFooter = false)
    {
        if (!$postData || !is_array($postData)) {
            return '<p>No post data found.</p>';
        }

        if ($postData['data']['status'] === 401) {
            return '<div class="wp-block-rrze-bluesky-bluesky"><p>Data not available</p></div>';
        }

        if ($postData['data']['status'] === 404) {
            return '<div class="wp-block-rrze-bluesky-bluesky"><p>Post not found</p></div>';
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
        // Check if this is an embedded record
        $isRecordEmbed = (isset($embed['$type']) && $embed['$type'] === 'app.bsky.embed.record#view');

        // Start building HTML
        $html  = '<div class="wp-block-rrze-bluesky-bluesky"><article class="bsky-post">';
        $html .= $this->renderPostHeader($handle, $displayName, $avatar);
        $html .= $this->renderPostContent($postText, $embed, $isVideoEmbed, $isRecordEmbed);
        $html .= $this->renderPostFooter($hideFooter, $handle, $uri, $createdAt, $likeCount, $repostCount, $replyCount);

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
        $response = wp_remote_get(home_url('wp-json/rrze-bluesky/v1/public-timeline?limit=' . $limit), [
            'headers' => [
                'X-RRZE-Secret-Key' => get_option('rrze_bluesky_secret_key'),
            ],
        ]);
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
    public function renderVidstackVideo($videoUrl, $poster = '', $videoId = '', $aspectRatioClass = 'ar-9-16')
    {
        $aspectRatio = '9/16';

        ob_start();
?>
        <div
            class="rrze-video-container"
            data-video-id="<?php echo esc_attr($videoId); ?>"
            style="--aspect-ratio: <?php echo esc_attr($aspectRatio); ?>;">
            <media-player
                class="<?php echo esc_attr($aspectRatioClass); ?>"
                src="<?php echo esc_url($videoUrl); ?>"
                poster="<?php echo esc_url($poster); ?>">
                <media-provider></media-provider>
                <media-video-layout></media-video-layout>
            </media-player>
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

    /**
     * Renders an embedded "record" post (app.bsky.embed.record#view).
     * We transform it into a normal post-data structure, then reuse renderPost().
     *
     * @param array $recordData Typically $embed['record'] from the parent post.
     * @return string HTML snippet
     */
    protected function renderEmbeddedRecord(array $recordData): string
    {
        if (empty($recordData) || !is_array($recordData)) {
            return '<p class="bsky-embedded-error">No valid embedded record data found.</p>';
        }

        // The "value" sub-array is the actual post content.
        if (empty($recordData['value']) || !is_array($recordData['value'])) {
            return '<p class="bsky-embedded-error">Embedded record has no post content.</p>';
        }

        // Re-map the embedded record into $postData so renderPost() can handle it
        $embeddedPost = [
            'author'      => $recordData['author'] ?? [],
            'record'      => $recordData['value']  ?? [],
            'uri'         => $recordData['uri']    ?? '',
            'likeCount'   => $recordData['likeCount']   ?? 0,
            'replyCount'  => $recordData['replyCount']  ?? 0,
            'repostCount' => $recordData['repostCount'] ?? 0,
            // If the embed record can contain further 'embed's, map them as well:
            'embed'       => $recordData['embeds'][0] ?? [],
        ];

        // Wrap in a container for styling
        return '<div class="bsky-embedded-record">'
            . $this->renderPost($embeddedPost, true)
            . '</div>';
    }

    /**
     * Retrieves the appropriate aspect ratio class for FAU video embeds.
     * 
     * This function determines the correct CSS class to apply based on 
     * the aspect ratio provided in the arguments. If no aspect ratio or an 
     * unrecognized aspect ratio is provided, it defaults to 'ar-16-9'.
     * 
     * @param array $arguments Associative array with the 'aspectratio' key potentially set to a string representing the desired aspect ratio.
     * @return string Returns the corresponding CSS class string based on the provided aspect ratio.
     * @since 3.5.1
     */
    public static function get_aspectratio_class($height, $width)
    {
        $ratio = round($height / $width, 2);
        switch (true) {
            case abs($ratio - (4 / 3)) < 0.01:
                return 'ar-4-3';
            case abs($ratio - (16 / 9)) < 0.01:
                return 'ar-16-9';
            default:
                return 'ar-9-16';
        }
    }

    /**
     * Render Starterpack List
     *
     * @param [type] $listData
     * @return void
     */
    public function renderStarterpackList($listData, $hstart = 2)
    {
        if (!$listData || !isset($listData['list'], $listData['items']) || empty($listData['items'])) {
            return '<p>No list data found.</p>';
        }

        $list  = $listData['list'];
        $items = $listData['items'];

        ob_start(); ?>
        <div class="wp-block-rrze-bluesky-bluesky">
            <h<?php echo (int) $hstart; ?>>
                <?php echo esc_html($list['name']); ?>
            </h<?php echo (int) $hstart; ?>>
            <?php if (!empty($list['description'])) : ?>
                <p><?php echo esc_html($list['description']); ?></p>
            <?php endif; ?>

            <ul class="bsky-starterpack-list">
                <?php
                // Reverse items if you wish
                foreach (array_reverse($items) as $item) :
                    $subj = $item['subject'] ?? null;
                    $displayName = $subj->displayName ?? '';
                    $handle      = $subj->handle ?? '';
                    $did         = $subj->did ?? '';
                    $avatar      = $subj->avatar ?? '';
                    $desc        = $subj->description ?? '';
                ?>
                    <li class="bsky-starterpack-list-item">
                        <div class="bsky-profile">
                            <?php if ($avatar): ?>
                                <img class="bsky-avatar"
                                    src="<?php echo esc_url($avatar); ?>"
                                    alt="<?php echo esc_attr($displayName); ?>" />
                            <?php endif; ?>

                            <div class="bsky-social-link">
                                <strong><?php echo esc_html($displayName); ?></strong><br />
                                <em>@<?php echo esc_html($handle); ?></em>
                            </div>

                            <a href="https://bsky.app/profile/<?php echo esc_attr($did); ?>"
                                class="bsky-follow-button"
                                aria-label="<?php printf('Follow %s', esc_attr($displayName)); ?>">
                                <svg class="bsky-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                                    <path fill="currentColor" d="M111.8 62.2C170.2 105.9 233 194.7 256 242.4c23-47.6 85.8-136.4 144.2-180.2c42.1-31.6 110.3-56 110.3 21.8c0 15.5-8.9 130.5-14.1 149.2C478.2 298 412 314.6 353.1 304.5c102.9 17.5 129.1 75.5 72.5 133.5c-107.4 110.2-154.3-27.6-166.3-62.9l0 0c-1.7-4.9-2.6-7.8-3.3-7.8s-1.6 3-3.3 7.8l0 0c-12 35.3-59 173.1-166.3 62.9c-56.5-58-30.4-116 72.5-133.5C100 314.6 33.8 298 15.7 233.1C10.4 214.4 1.5 99.4 1.5 83.9c0-77.8 68.2-53.4 110.3-21.8z" />
                                </svg>
                                <?php esc_html_e('Follow', 'rrze-bluesky'); ?>
                            </a>
                        </div>

                        <?php if ($desc): ?>
                            <p><?php echo esc_html($desc); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
<?php
        return ob_get_clean();
    }

    public function retrieveStarterpackInformation($uri)
    {
        if (empty($uri)) {
            return null;
        }

        $endpoint = home_url('wp-json/rrze-bluesky/v1/list');
        $response = wp_remote_get(add_query_arg(['starterPack' => urlencode($uri)], $endpoint), [
            'headers' => [
                'X-RRZE-Secret-Key' => get_option('rrze_bluesky_secret_key'),
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }


    /**
     * Renders a personal Bluesky profile card given a handle (e.g. "example.bsky.social").
     *
     * @param string $bskyHandle The Bluesky handle to fetch (without "@" prefix).
     * @return string            HTML content to render the profile or error message.
     */
    public function renderPersonalProfile($bskyHandle)
    {
        $renderer = new self();

        $api = $renderer->getApi();
        if (!$api) {
            return '<p>No API object available.</p>';
        }

        $profileData = $api->getProfile(['actor' => $bskyHandle]);
        if (!$profileData) {
            return '<p>No Bluesky profile found for: ' . esc_html($bskyHandle) . '</p>';
        }

        $displayName = !empty($profileData->displayName) ? $profileData->displayName : $bskyHandle;
        $handle      = !empty($profileData->handle)      ? $profileData->handle      : $bskyHandle;
        $avatar      = !empty($profileData->avatar)      ? $profileData->avatar      : '';
        $description = !empty($profileData->description) ? $profileData->description : '';

        // Build the HTML card:
        $html  = '<div class="wp-block-rrze-bluesky-bluesky">';
        $html .= '  <div class="bsky-profile-card">';
        if ($avatar) {
            $html .= '    <div class="bsky-profile-avatar">';
            $html .= '      <img style="height:80px; width:80px; border-radius:50%;" src="' . esc_url($avatar) . '" alt="' . esc_attr($displayName) . '" />';
            $html .= '    </div>';
        }
        $html .= '    <div class="bsky-profile-details">';
        $html .= '      <h3>' . esc_html($displayName) . '</h3>';
        $html .= '      <p>@' . esc_html($handle) . '</p>';
        if (!empty($description)) {
            $html .= '      <p>' . esc_html($description) . '</p>';
        }
        $html .= '    </div>'; // .bsky-profile-details
        $html .= '  </div>';   // .bsky-profile-card
        $html .= '</div>';

        return $html;
    }
}
