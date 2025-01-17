<?php

namespace RRZE\Bluesky;

use WP_Error;
use WP_REST_Request;

defined('ABSPATH') || exit;

class REST
{
    private $api;

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        $data_encryption = new Encryption();
        $username = $data_encryption->decrypt(get_option('rrze_bluesky_username'));
        $password = $data_encryption->decrypt(get_option('rrze_bluesky_password'));

        // Instantiate the API
        $api = new API($username, $password);
        $this->setApi($api);
    }

    /** 
     * Setter for API 
     */
    public function setApi($api)
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
     * Register the different routes for the REST API
     */
    public function registerRestRoutes()
    {
        register_rest_route('rrze-bluesky/v1', '/public-timeline', [
            'methods' => 'GET',
            'callback' => [$this, 'getPublicTimeline'],
            'permission_callback' => [$this, 'permissionCheck'],
        ]);

        // register_rest_route('rrze_bluesky/v1', '/list', [
        //     'methods' => 'GET',
        //     'callback' => [$this, 'getList'],
        // ]);

        // register_rest_route('rrze_bluesky/v1', '/user', [
        //     'methods' => 'GET',
        //     'callback' => [$this, 'getUserProfile'],
        // ]);

        register_rest_route('rrze-bluesky/v1', '/post', [
            'methods' => 'GET',
            'callback' => [$this, 'getPost'],
            'args' => [
                'uri' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
            ],
        ]);
    }

    public function getPost(WP_REST_Request $request)
    {
        $api = $this->getApi();

        $uri = $request->get_param('uri');
        if (!$uri) {
            return new WP_Error(
                'missing_uri',
                __('No "uri" parameter provided.', 'rrze-bluesky'),
                ['status' => 400]
            );
        }

        $token = $api->getAccessToken();
        if (!$token) {
            Helper::debug('Fehler bei der Authentifizierung.');
            return new WP_Error(
                'no_token',
                __('Authentication failed.', 'rrze-bluesky'),
                ['status' => 401]
            );
        }

        if (!str_starts_with($uri, 'at://')) {
            $converted = $this->convertBskyLinkToAtUri($uri);
            if (!$converted) {
                return new WP_Error(
                    'invalid_uri',
                    __('Provided URI is neither a valid at:// URI nor a valid bsky.app link.', 'rrze-bluesky'),
                    ['status' => 400]
                );
            }
            $uri = $converted;
        }

        $post = $api->getPosts($uri);

        if (!$post) {
            return new WP_Error(
                'post_not_found',
                __('No post found for the given URI.', 'rrze-bluesky'),
                ['status' => 404]
            );
        }

        return $post;
    }

    /**
     * Converts a "https://bsky.app/profile/<handle>/post/<postId>" link
     * into a valid "at://did:plc:xxxx/app.bsky.feed.post/<postId>" URI
     * by resolving the handle into a DID via getProfile().
     * 
     * Returns the original string if it *already* starts with "at://".
     * Returns null if it can't parse or resolve the link.
     */
    private function convertBskyLinkToAtUri(string $url): ?string
    {
        // Quick check: if user already supplied an at:// URI
        if (str_starts_with($url, 'at://')) {
            return $url;
        }

        // Example: https://bsky.app/profile/ej64ojyw.bsky.social/post/3lfffxovnes2m
        $host = parse_url($url, PHP_URL_HOST);  // e.g. "bsky.app"
        $path = parse_url($url, PHP_URL_PATH);  // e.g. "/profile/ej64ojyw.bsky.social/post/3lfffxovnes2m"

        if (!$host || !$path || !str_contains($host, 'bsky.app')) {
            return null; // Not a recognized bsky link
        }

        $segments = explode('/', trim($path, '/'));
        // segments => ["profile","ej64ojyw.bsky.social","post","3lfffxovnes2m"]
        if (count($segments) < 4 || $segments[0] !== 'profile' || $segments[2] !== 'post') {
            return null;
        }

        $handle = $segments[1]; // e.g. "ej64ojyw.bsky.social"
        $postId = $segments[3]; // e.g. "3lfffxovnes2m"

        $profile = $this->getApi()->getProfile(['actor' => $handle]);

        if (!$profile || !$profile->did) {
            return null;
        }

        // Build a valid AT URI: "at://did:plc:xxxx/app.bsky.feed.post/3lfffxovnes2m"
        return sprintf('at://%s/app.bsky.feed.post/%s', $profile->did, $postId);
    }


    /**
     * Get the public timeline
     */
    public function getPublicTimeline()
    {
        $cache_key = 'rrze_bluesky_public_timeline';
        $cached_timeline = get_transient($cache_key);

        if ($cached_timeline !== false) {
            return $cached_timeline;
        }

        $api = $this->getApi();
        $token = $api->getAccessToken();
        if (!$token) {
            Helper::debug('Fehler bei der Authentifizierung.');
        } else {
            Helper::debug('Erfolgreich authentifiziert. Token:');
        }

        $timeline = $api->getPublicTimeline();

        // Cache the result for 1 hour
        set_transient($cache_key, $timeline, HOUR_IN_SECONDS);

        return $timeline;
    }

    /**
     * Check if user can access this REST endpoint
     */
    public function permissionCheck(WP_REST_Request $request)
    {
        // Example: Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You are not allowed to access this endpoint.', 'text-domain'),
                ['status' => 401]
            );
        }

        // Optionally check for capabilities: current_user_can('manage_options')
        return true;
    }
}
