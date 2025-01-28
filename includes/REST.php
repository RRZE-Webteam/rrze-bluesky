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

        register_rest_route('rrze-bluesky/v1', '/starter-pack', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getStarterPackHandler'],
            'permission_callback' => [$this, 'permissionCheck'], // or a custom callback
            'args' => [
                'starterPack' => [
                    'required' => true,
                    'type'     => 'string',
                    'description' => 'The at-uri of the starter pack record.',
                ],
            ],
        ]);

        register_rest_route(
            'rrze-bluesky/v1',
            '/list',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getListHandler'],
                'permission_callback' => [$this, 'permissionCheck'], // adjust if needed
                'args'                => [
                    'list' => [
                        'required'    => false,
                        'type'        => 'string',
                        'description' => 'The AT-URI of the list.',
                    ],
                    'starterPack' => [
                        'required'    => false,
                        'type'        => 'string',
                        'description' => 'The AT-URI of the starter pack.',
                    ],
                    'limit' => [
                        'required'    => false,
                        'type'        => 'integer',
                        'description' => 'The max number of items to fetch (1..100). Default is 50.',
                    ],
                    'cursor' => [
                        'required'    => false,
                        'type'        => 'string',
                        'description' => 'Pagination cursor for the next page of results.',
                    ],
                ],
            ]
        );
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

        $cache_key = 'rrze_bluesky_post_' . md5($uri);
        $cached_post = get_transient($cache_key);

        if ($cached_post !== false) {
            return $cached_post;
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

        set_transient($cache_key, $post, HOUR_IN_SECONDS);

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
        // if (!is_user_logged_in()) {
        //     return new WP_Error(
        //         'rest_forbidden',
        //         esc_html__('You are not allowed to access this endpoint.', 'text-domain'),
        //         ['status' => 401]
        //     );
        // }

        // Optionally check for capabilities: current_user_can('manage_options')
        return true;
    }

    /**
     * Handler for GET /rrze-bluesky/v1/starter-pack
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    public function getStarterPackHandler(\WP_REST_Request $request)
    {
        $starterPackUri = $request->get_param('starterPack');
        if (!$starterPackUri) {
            return new \WP_Error(
                'missing_starterpack_param',
                __('No "starterPack" parameter provided.', 'rrze-bluesky'),
                ['status' => 400]
            );
        }

        // Automatically convert if it's not already "at://..."
        if (!str_starts_with($starterPackUri, 'at://')) {
            $converted = $this->convertBskyStarterPackLinkToAtUri($starterPackUri);
            if (!$converted) {
                return new \WP_Error(
                    'invalid_starterpack_uri',
                    __(
                        'Provided param is neither a valid at:// URI nor a recognized bsky.app/starter-pack link.',
                        'rrze-bluesky'
                    ),
                    ['status' => 400]
                );
            }
            $starterPackUri = $converted;
        }

        // Optionally, check a transient cache
        $cache_key = 'rrze_bluesky_starter_pack_' . md5($starterPackUri);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        // If endpoint requires auth, call getAccessToken():
        $api = $this->getApi();
        $token = $api->getAccessToken();
        if (!$token) {
            return new \WP_Error(
                'no_token',
                __('Authentication failed.', 'rrze-bluesky'),
                ['status' => 401]
            );
        }

        // Call your $api->getStarterPack() with the newly-converted at:// URI
        try {
            $starterPackData = $api->getStarterPack($starterPackUri);
            if (!$starterPackData) {
                return new \WP_Error(
                    'starter_pack_not_found',
                    __('No data returned for the given starterPack URI.', 'rrze-bluesky'),
                    ['status' => 404]
                );
            }

            set_transient($cache_key, $starterPackData, HOUR_IN_SECONDS);
            return $starterPackData;
        } catch (\Exception $e) {
            return new \WP_Error(
                'starter_pack_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Handler for GET /rrze-bluesky/v1/list
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
/**
 * Handler for GET /rrze-bluesky/v1/list
 *
 * Accepts either:
 *   - ?list=at://did:xxx/app.bsky.graph.list/yyy
 *   - OR ?starterPack=at://did:xxx/app.bsky.starterpack/zzz (or a bsky.app link)
 * 
 * If 'starterPack' is given, we call getStarterPackHandler internally,
 * then use its 'list.uri' as the 'list' param.
 */
public function getListHandler(WP_REST_Request $request)
{
    $listParam       = $request->get_param('list');         // The direct AT-URI of the list
    $starterPackParam = $request->get_param('starterPack'); // Alternatively, a starter-pack param
    $limit           = $request->get_param('limit');        // Optional
    $cursor          = $request->get_param('cursor');       // Optional

    // 1) If user didn't pass 'list' but provided 'starterPack', 
    //    we retrieve that starter pack to find the list.uri
    if (!$listParam && $starterPackParam) {
        // Construct a mock request to call getStarterPackHandler
        $mockRequest = new WP_REST_Request('GET', '/rrze-bluesky/v1/starter-pack');
        $mockRequest->set_param('starterPack', $starterPackParam);

        // Re-use the logic in getStarterPackHandler
        $starterPackResponse = $this->getStarterPackHandler($mockRequest);

        if (is_wp_error($starterPackResponse)) {
            // If there's an error, return it immediately
            return $starterPackResponse;
        }
        // $starterPackResponse is an array containing "starterPack", e.g.:
        // { "starterPack": { "list": { "uri": "at://did:.../app.bsky.graph.list/..." }, ... } }
        $listUri = $starterPackResponse['starterPack']['list']['uri'] ?? null;
        if (!$listUri) {
            return new WP_Error(
                'no_list_uri_found',
                __('No "list.uri" found in the retrieved starter pack.', 'rrze-bluesky'),
                ['status' => 400]
            );
        }
        // Now we have the actual list AT-URI
        $listParam = $listUri;
    }

    // 2) If we still have no "list" param at this point, bail
    if (!$listParam) {
        return new WP_Error(
            'missing_list',
            __('No "list" parameter provided. Provide either ?list=... or ?starterPack=....', 'rrze-bluesky'),
            ['status' => 400]
        );
    }

    // Build the arguments for getList()
    $args = [
        'list' => $listParam,
    ];
    if ($limit) {
        $args['limit'] = (int) $limit;
    }
    if ($cursor) {
        $args['cursor'] = $cursor;
    }

    // Build a cache key based on these args
    $cache_key = 'rrze_bluesky_get_list_' . md5(json_encode($args));
    $cached_data = get_transient($cache_key);
    if (false !== $cached_data) {
        return $cached_data;
    }

    // Retrieve the access token if needed
    $api = $this->getApi();
    $token = $api->getAccessToken();
    if (!$token) {
        return new WP_Error(
            'no_token',
            __('Authentication failed or no token available.', 'rrze-bluesky'),
            ['status' => 401]
        );
    }

    // Finally, call the API to get the list data
    try {
        $data = $api->getList($args);
        if (!$data) {
            return new WP_Error(
                'list_not_found',
                __('No list data returned for the given AT-URI.', 'rrze-bluesky'),
                ['status' => 404]
            );
        }

        set_transient($cache_key, $data, HOUR_IN_SECONDS);
        return $data;

    } catch (\Exception $e) {
        return new WP_Error(
            'list_error',
            $e->getMessage(),
            ['status' => 500]
        );
    }
}

    /**
     * Converts a "https://bsky.app/starter-pack/<handle-or-domain>/<recordId>" link
     * into a valid "at://did:plc:xxxx/app.bsky.starterpack/<recordId>" URI
     * by resolving the <handle-or-domain> into a DID via getProfile().
     *
     * Returns the original string if it *already* starts with "at://".
     * Returns null if it can't parse or resolve the link.
     */
    private function convertBskyStarterPackLinkToAtUri(string $url): ?string
    {
        if (str_starts_with($url, 'at://')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);  // e.g. "bsky.app"
        $path = parse_url($url, PHP_URL_PATH);  // e.g. "/starter-pack/fau.de/3lbr3pd4ooq2q"

        if (!$host || !$path || !str_contains($host, 'bsky.app')) {
            return null; // Not a recognized bsky link
        }

        $segments = explode('/', trim($path, '/'));
        if (count($segments) < 3 || $segments[0] !== 'starter-pack') {
            return null; // Not in the expected format
        }

        $handle   = $segments[1]; // e.g. "fau.de"
        $recordId = $segments[2]; // e.g. "3lbr3pd4ooq2q"

        $api = $this->getApi();
        $api->getAccessToken();

        $profile = $api->getProfile(['actor' => $handle]);
        if (!$profile || empty($profile->did)) {
            return null;
        }

        // Build a valid AT URI:
        // "at://did:plc:xxxx/app.bsky.starterpack/<recordId>"
        return sprintf('at://%s/app.bsky.starterpack/%s', $profile->did, $recordId);
    }
}
