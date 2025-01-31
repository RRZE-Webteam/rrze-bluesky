<?php

namespace RRZE\Bluesky;

use RRZE\Bluesky\Helper;

class API
{
    private string $baseUrl = "https://bsky.social/xrpc";
    private ?string $token = null;
    private ?string $refreshToken = null;
    private string $username;
    private string $password;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        // I | Attempt: Load Tokens from Transient if set
        $storedRefreshToken = get_transient('rrze_bluesky_refresh_token');
        $storedAccessToken = get_transient('rrze_bluesky_access_token');

        if ($storedRefreshToken) {
            $this->refreshToken = $storedRefreshToken;
        }

        if ($storedAccessToken) {
            $this->token = $storedAccessToken;
        }

        // II | Attempt: Login if no tokens are set
        if (!$this->token) {
            $this->getAccessToken();
        }
    }

    /**
     * Retrieve the access token for the current user and store it as transient.
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        if ($this->token) {
            return $this->token;
        }

        $url = "{$this->baseUrl}/com.atproto.server.createSession";
        $data = [
            "identifier"    => $this->username,
            "password"      => $this->password,
        ];

        $response = $this->makeRequest($url, "POST", $data);
        if (is_wp_error($response)) {
            Helper::debug("Authentication error: " . $response->get_error_message());
            return null;
        }

        if (!empty($response['accessJwt']) && !empty($response['refreshJwt'])) {
            $this->token = $response['accessJwt'];
            $this->refreshToken = $response['refreshJwt'];

            // Store the tokens as transients
            set_transient('rrze_bluesky_access_token',  $this->token,        HOUR_IN_SECONDS);
            set_transient('rrze_bluesky_refresh_token', $this->refreshToken, DAY_IN_SECONDS);

            return $this->token;
        }

        // If the API doesn't return a valid token, we can't proceed
        Helper::debug("No valid token fields found in login response.");
        return null;
    }

    private function requireAccessToken(): void
    {
        // If we already have a token, we're good
        if ($this->token) {
            return;
        }

        // If we have a refresh token, try to refresh
        if ($this->refreshToken) {
            $refreshed = $this->refreshSession();
            if ($refreshed && $this->token) {
                return;
            }
        }

        // Fallback: Attempt to get a new token
        $this->getAccessToken();

        if (!$this->token) {
            Helper::debug("No valid access token available.");
            return;
        }
    }


    /**
     * Refresh the access token using our refresh token.
     * Official endpoint: POST /xrpc/com.atproto.server.refreshSession
     *
     * @return bool True on success, false on failure
     */
    private function refreshSession(): bool
    {
        if (empty($this->refreshToken)) {
            Helper::debug("No refresh token available.");
            return false;
        }

        $url = "{$this->baseUrl}/com.atproto.server.refreshSession";
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->refreshToken}",
            ],
            'body' => '{}',
        ];

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            Helper::debug("Refresh session error: " . $response->get_error_message());
            $this->removeStaleRefreshAndAccessToken();
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($decoded['accessJwt']) || empty($decoded['refreshJwt'])) {
            Helper::debug("Invalid refresh response: " . $body);
            $this->removeStaleRefreshAndAccessToken();
            return false;
        }

        // Update our tokens
        $this->token        = $decoded['accessJwt'];
        $this->refreshToken = $decoded['refreshJwt'];

        // Re-set the transients for the new tokens 
        set_transient('rrze_bluesky_access_token',  $this->token,        HOUR_IN_SECONDS);
        set_transient('rrze_bluesky_refresh_token', $this->refreshToken, DAY_IN_SECONDS);

        Helper::debug("Access token successfully refreshed.");
        return true;
    }

    private function removeStaleRefreshAndAccessToken(): void
    {
        $this->refreshToken = null;
        delete_transient('rrze_bluesky_refresh_token');
        $this->token = null;
        delete_transient('rrze_bluesky_access_token');
        Helper::debug("Removed stale refresh token after failed attempt.");
    }


    /**
     * Retrieve the public feed.
     *
     * @param string $did User's DID (f.E. "did:plc:12345").
     * @return array|null The public feed or null on error.
     */
    public function getAuthorFeed(string $did): ?array
    {
        $this->requireAccessToken();
        $url = "{$this->baseUrl}/app.bsky.feed.getAuthorFeed?actor={$did}";

        return $this->makeRequest($url, "GET");
    }

    /**
     * Retrieve the public feed.
     *
     * @param string $uri . Bspw: at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.feed.post/3lemy4yerrk27
     * @return array|null . Die Daten des Posts, wenn gefunden
     */
    public function getPosts(string $uri)
    {
        $this->requireAccessToken();
        $data = [
            'uris' => [$uri], // AT URI(s) as input
        ];
        $url = "{$this->baseUrl}/app.bsky.feed.getPosts";
        $response = $this->makeRequest($url, "GET", $data);

        if (!$response || empty($response['posts'])) {
            Helper::debug("No post found for: $uri");
            return null;
        }

        // Extract the first post
        $postData = $response['posts'][0]; 
        return $postData;
    }


    /**
     * Retrieve the public feed.
     *
     * @return array|null The public feed or null on error.
     */
    public function getPublicTimeline(): ?array
    {
        $this->requireAccessToken();

        $url = "{$this->baseUrl}/app.bsky.feed.getTimeline";

        $response = $this->makeRequest($url, "GET");

        if (is_wp_error($response)) {
            return null;
        }

        return $response;
    }

    /**
     * Search for a list
     * @param array search, with (at-identifier) actor as required, (int) limit optional, (strng) cursor optional
     * @return array|null List or null on not found
     */
    public function getLists(array $search): ?array
    {
        $this->requireAccessToken();
        if (empty($search['actor'])) {
            Helper::debug('Required field actor (of type at-identifier) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.graph.getLists";
        $response = $this->makeRequest($url, "GET", $search);

        if (!$response || !isset($response['lists']) || !is_array($response['lists'])) {
            return null;
        }

        $listsObjects = [];
        foreach ($response['lists'] as $num => $listData) {

            $listsObjects[] = new Lists($listData);
        }

        return $listsObjects;
    }

    /**
     * Search for a list
     * @param array search, with (at-uri) list as required, (int) limit optional, (strng) cursor optional
     * @return array|null List or null on not found
     */
    public function getList(array $search): ?array
    {
        $this->requireAccessToken();
        if (empty($search['list'])) {
            Helper::debug('Required field list (of type at-uri) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.graph.getList";

        $response = $this->makeRequest($url, "GET", $search);

        if (!$response) {
            return null;
        }

        if (!isset($response['list'], $response['items'])) {
            return null;
        }

        $listsObject = new Lists($response['list']);
        $cursor = isset($response['cursor']) ? (string) $response['cursor'] : '';

        $items = [];
        if (isset($response['items']) && is_array($response['items'])) {
            foreach ($response['items'] as $item) {
                $uri = $item['uri'] ?? '';

                $profilObj = null;
                if (isset($item['subject']) && is_array($item['subject'])) {
                    $profilObj = new Profil($item['subject']);
                }

                $items[] = [
                    'uri'     => $uri,
                    'subject' => $profilObj
                ];
            }
        }

        return [
            'cursor' => $cursor,
            'list'   => $listsObject,
            'items'  => $items
        ];
    }

    /**
     * Get an account
     * @param actor
     * @return Profil|null Return a Profil object or null if not found
     */
    public function getProfile(array $search): ?Profil
    {
        $this->requireAccessToken();
        if (empty($search['actor'])) {
            Helper::debug('Required field actor missing.');
        }

        $url = "{$this->baseUrl}/app.bsky.actor.getProfile";
        $response = $this->makeRequest($url, "GET", $search);

        if (!$response) {
            Helper::debug("Keine Antwort vom Server.");
            return null;
        }

        if (is_array($response)) {
            return new Profil($response);
        }

        Helper::debug("Invalid response from server.");
        return null;
    }

    /**
     * Get a view of a starter pack.
     *
     * @param string $starterPackUri The at-uri for the starter pack.
     * @return array|null The starter pack data, or null if not found.
     * @throws \Exception
     */
    public function getStarterPack(string $starterPackUri): ?array
    {
        $this->requireAccessToken();
        if (empty($starterPackUri)) {
            Helper::debug("starterPack parameter is required.");
        }

        $url = "{$this->baseUrl}/app.bsky.graph.getStarterPack";

        $data = [
            'starterPack' => $starterPackUri,
        ];

        $response = $this->makeRequest($url, "GET", $data);

        if (!$response) {
            return null;
        }

        if (!isset($response['starterPack'])) {
            return null;
        }

        return $response;
    }

    /**
     * Retrieve ALL items of a starter pack in one pass, caching in transient.
     *
     * @param string $starterPackUri at://... or https://bsky.app/starter-pack/... link
     * @return array|null Returns [
     *   'list' => [ ... ],
     *   'items' => [ ... ]
     * ] or null on error.
     */
    public function getAllStarterPackData(string $starterPackUri): ?array
    {
        if (empty($starterPackUri)) {
            Helper::debug("No starter pack URI provided.");
            return null;
        }

        // Use a transient to cache for an hour
        $cacheKey = 'rrze_bluesky_starterpack_all_' . md5($starterPackUri);
        $cached   = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        // Convert bsky.app link -> at:// if needed
        if (!str_starts_with($starterPackUri, 'at://')) {
            $converted = $this->convertBskyStarterPackLinkToAtUri($starterPackUri);
            if (!$converted) {
                Helper::debug("Invalid or unresolvable starterPack URI/link: " . $starterPackUri);
                return null;
            }
            $starterPackUri = $converted;
        }

        // Retrieve the Starter Pack info
        $spResponse = $this->getStarterPack($starterPackUri);
        if (!$spResponse || empty($spResponse['starterPack']['list']['uri'])) {
            Helper::debug("No 'list.uri' found in starter pack response.");
            return null;
        }

        // That 'list' array has a 'uri' of the actual list: at://did:xx/app.bsky.graph.list/xxxx
        $listUri   = $spResponse['starterPack']['list']['uri'];
        $listName  = $spResponse['starterPack']['list']['name'] ?? 'Bluesky Starter Pack';
        $listDesc  = $spResponse['starterPack']['list']['purpose'] ?? '';

        // 4) Now gather *all* items from that list by looping over the cursor
        $allItems = [];
        $cursor   = '';
        $limit    = 100;  // Bsky typically allows up to 100

        do {
            $res = $this->getList([
                'list'   => $listUri,
                'limit'  => $limit,
                'cursor' => $cursor
            ]);
            if (!$res) {
                break;
            }
            // Merge items
            if (!empty($res['items'])) {
                $allItems = array_merge($allItems, $res['items']);
            }

            $cursor = $res['cursor'] ?? '';
        } while (!empty($cursor) && count($allItems) < 300);

        // 5) Build final array
        $output = [
            'list' => [
                'uri'         => $listUri,
                'name'        => $listName,
                'description' => $listDesc
            ],
            'items' => $allItems
        ];

        // Cache it for 1 hour
        set_transient($cacheKey, $output, HOUR_IN_SECONDS);

        return $output;
    }

    /**
     * Converts a "https://bsky.app/starter-pack/<handle-or-domain>/<recordId>" link
     * into a valid "at://did:plc:xxxx/app.bsky.starterpack/<recordId>" URI by
     * resolving the <handle-or-domain> into a DID via getProfile().
     *
     * If already at://, returns unchanged. Returns null on parse or resolution failure.
     *
     * @param string $url The bsky starter-pack link
     * @return string|null The converted at:// link or null
     */
    private function convertBskyStarterPackLinkToAtUri(string $url): ?string
    {
        // Quick check
        if (str_starts_with($url, 'at://')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        if (!$host || !$path || !str_contains($host, 'bsky.app')) {
            return null; // not recognized
        }

        $segments = explode('/', trim($path, '/'));
        // e.g. "starter-pack", "example.bsky.social", "someRecordId"
        if (count($segments) < 3 || $segments[0] !== 'starter-pack') {
            return null;
        }

        [$packSlug, $handleOrDomain, $recordId] = $segments;

        // Resolve handle -> DID
        $profile = $this->getProfile(['actor' => $handleOrDomain]);
        if (!$profile || empty($profile->did)) {
            return null;
        }

        // Build "at://did:xxx/app.bsky.starterpack/<recordId>"
        return sprintf('at://%s/app.bsky.starterpack/%s', $profile->did, $recordId);
    }

    /**
     * Performs a HTTP request to Bluesky.
     *
     * @param  string      $url
     * @param  string      $method  "GET" or "POST"
     * @param  array|null  $data    Optional query params or POST body
     * @return array|WP_Error       Returns decoded JSON as an array, or WP_Error on failure
     */
    private function makeRequest(string $url, string $method, ?array $data = null)
    {
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        // If we have an access token, include it
        if ($this->token) {
            $args['headers']['Authorization'] = "Bearer {$this->token}";
        }

        // Build request
        if ($method === "POST" && $data) {
            $args['body'] = json_encode($data);
        }

        if ($method === "GET" && $data) {
            $queryString = http_build_query($data);
            $url .= '?' . $queryString;
        }

        // Execute request
        $response = ($method === "POST")
            ? wp_remote_post($url, $args)
            : wp_remote_get($url, $args);

        // Check for WP errors
        if (is_wp_error($response)) {
            return $response;
        }

        // Check HTTP status code
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 401 || $status_code === 403) {
            // Try to refresh and retry once:
            Helper::debug("Token might be expired. Attempting to refresh.");
            if ($this->refreshSession()) {
                // Rebuild headers with the new access token
                if ($method === "POST" && $data) {
                    $args['body'] = json_encode($data);
                }
                $args['headers']['Authorization'] = "Bearer {$this->token}";

                $response = ($method === "POST")
                    ? wp_remote_post($url, $args)
                    : wp_remote_get($url, $args);

                if (is_wp_error($response)) {
                    return $response;
                }
            } else {
                Helper::debug("Refresh session failed. Not retrying further.");
            }
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Helper::debug('Failed to decode JSON: ' . json_last_error_msg());
            return null;
        }

        return $decoded;
    }
}
