<?php

namespace RRZE\Bluesky;

use RRZE\Bluesky\Helper;

class API
{
    private string $baseUrl = "https://bsky.social/xrpc";
    private ?string $token = null;
    private ?string $refreshToken = null;
    private ?Config $config = null;
    private $username;
    private $password;

    public function __construct($username, $password, ?Config $config = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->config = $config;

        if (!empty($this->config) && !empty($this->config->get('service_baseurl'))) {
            $this->baseUrl = $this->config->get('service_baseurl');
        }

        // Load Refresh Token from transient if set
        $storedRefreshToken = get_transient('rrze_bluesky_refresh_token');
        Helper::debug('Stored refresh token: ' . $storedRefreshToken);
        if ($storedRefreshToken) {
            $this->refreshToken = $storedRefreshToken;
            Helper::debug('Loaded refresh token from transient.');
        }

        // Load Access Token from transient if set
        $storedAccessToken = get_transient('rrze_bluesky_access_token');
        Helper::debug('Stored access token: ' . $storedAccessToken);
        if ($storedAccessToken) {
            $this->token = $storedAccessToken;
            Helper::debug('Loaded access token from transient.');
        }
    }

    /**
     * Loggt sich ein und speichert das Access-Token.
     *
     * @param string $username Bluesky-Benutzername.
     * @param string $password Bluesky-Passwort.
     * @return string|null Das Access-Token oder null, falls der Login fehlschlägt.
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

        Helper::debug($response);

        if (isset($response['accessJwt']) && isset($response['refreshJwt'])) {
            $this->token = $response['accessJwt'];
            $this->refreshToken = $response['refreshJwt'];

            $accessTokenTTL  = 3600;  // 1 hour
            $refreshTokenTTL = 86400; // 1 day
            set_transient('rrze_bluesky_access_token',  $this->token,        $accessTokenTTL);
            set_transient('rrze_bluesky_refresh_token', $this->refreshToken, $refreshTokenTTL);

            Helper::debug("Access token retrieved successfully.");
            return $this->token;
        }

        Helper::debug("Failed to retrieve access token.");
        return null;
    }

    /**
     * Ruft die Beiträge eines bestimmten Benutzers ab.
     *
     * @param string $did Die DID des Benutzers (z. B. "did:plc:12345").
     * @return array|null Die Beiträge des Benutzers oder null bei Fehlern.
     */
    public function getAuthorFeed(string $did): ?array
    {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        $params = '';
        if (!empty($this->config->get('query_getAuthorFeed'))) {
            if (isset($this->config->get('query_getAuthorFeed')['filter'])) {
                $params .= '&filter=' . $this->config->get('query_getAuthorFeed')['filter'];
            }
            if (isset($this->config->get('query_getAuthorFeed')['limit'])) {
                $params .= '&limit=' . $this->config->get('query_getAuthorFeed')['limit'];
            }
        }

        $url = "{$this->baseUrl}/app.bsky.feed.getAuthorFeed?actor={$did}";
        if (!empty($params)) {
            $url .= $params;
        }

        return $this->makeRequest($url, "GET");
    }

    /**
     * Ruft einen bestimmten Post auf.
     *
     * @param string $uri . Bspw: at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.feed.post/3lemy4yerrk27
     * @return array|null . Die Daten des Posts, wenn gefunden
     */
    public function getPosts(string $uri)
    {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        $data = [
            'uris' => [$uri], // AT URI(s) as input
        ];
        $url = "{$this->baseUrl}/app.bsky.feed.getPosts";

        // API-Aufruf über die makeRequest-Methode
        $response = $this->makeRequest($url, "GET", $data);

        Helper::debug("response");
        Helper::debug($response);
        if (!$response || empty($response['posts'])) {
            error_log("No post found for: $uri");
            return null;
        }

        // Den ersten Post im Array nehmen (da wir nur einen URI übergeben haben)
        $postData = $response['posts'][0];
        // Rückgabe des Posts als Post-Objekt
        return $postData;
    }


    /**
     * Beispiel: Ruft die öffentliche Timeline ab.
     *
     * @return array|null Die öffentliche Timeline oder null bei Fehlern.
     */
    public function getPublicTimeline(): ?array
    {
        if (!$this->token) {
            Helper::debug("Access token is required. Call getAccessToken() first.");
        }

        $url = "{$this->baseUrl}/app.bsky.feed.getTimeline";

        $response = $this->makeRequest($url, "GET");

        if (is_wp_error($response)) {
            Helper::debug("Error fetching public timeline: " . $response->get_error_message());
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
        if (!$this->token) {
            Helper::debug("Access token is required. Call getAccessToken() first.");
        }
        if (empty($search['actor'])) {
            Helper::debug('Required field actor (of type at-identifier) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.graph.getLists";

        // Werte aus der Config laden
        $configParams = $this->config->get('query_getlists') ?? [];

        // Fehlende Werte aus der Config ergänzen
        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }
        $response = $this->makeRequest($url, "GET", $search);

        // Falls keine sinnvolle Antwort vorliegt oder 'lists' nicht vorhanden ist, null zurückgeben
        if (!$response || !isset($response['lists']) || !is_array($response['lists'])) {
            return null;
        }

        // Jedes Element in 'lists' in ein Objekt der Klasse Lists umwandeln
        $listsObjects = [];
        foreach ($response['lists'] as $num => $listData) {

            $listsObjects[] = new Lists($listData, $this->config);
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
        if (!$this->token) {
            Helper::debug("Access token is required. Call getAccessToken() first.");
        }
        if (empty($search['list'])) {
            Helper::debug('Required field list (of type at-uri) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.graph.getList";

         $response = $this->makeRequest($url, "GET", $search);

        // Falls keine gültige Antwort vorliegt, Abbruch mit null
        if (!$response) {
            return null;
        }

        // Erwartete Felder prüfen
        if (!isset($response['list'], $response['items'])) {
            // Wenn Felder fehlen, kann man hier entweder null oder eine Exception werfen
            return null;
        }

        // Umwandeln des Feldes 'list' in ein Listen-Objekt (Class Lists)
        $listsObject = new Lists($response['list'], $this->config);
        $cursor = isset($response['cursor']) ? (string) $response['cursor'] : '';

        $items = [];
        if (isset($response['items']) && is_array($response['items'])) {
            foreach ($response['items'] as $item) {
                // uri als string
                $uri = $item['uri'] ?? '';

                // subject als Profil-Objekt
                $profilObj = null;
                if (isset($item['subject']) && is_array($item['subject'])) {
                    $profilObj = new Profil($item['subject'], $this->config);
                }

                $items[] = [
                    'uri'     => $uri,
                    'subject' => $profilObj
                ];
            }
        }

        // Rückgabe als assoziatives Array
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
        if (!$this->token) {
            Helper::debug("Access token is required. Call getAccessToken() first.");
        }
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
            return new Profil($response, $this->config);
        }
    
        Helper::debug("Invalid response from server.");
        return null; // <--- Ensure we return something (null) here as well
    }
    

    /*
     * Suchanfrage
     */
    public function searchPosts(array $search): ?array
    {
        $endpoint = "{$this->baseUrl}/app.bsky.feed.searchPosts";

        if (empty($search['q'])) {
            throw new \InvalidArgumentException('Required field q for the search string is missing.');
        }

        $configParams = $this->config->get('query_searchPosts') ?? [];

        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }

        $response = $this->makeRequest($endpoint, 'GET', $search);

        if (!$response) {
            error_log('No results found.');
            return null;
        }

        if (!isset($response['posts'])) {
            throw new \RuntimeException('Invalid api response');
        }

        $posts = [];
        foreach ($response['posts'] as $postData) {
            $posts[] = new Post($postData);
        }

        return [
            'cursor' => $response['cursor'] ?? '',
            'hitsTotal' => (int) ($response['hitsTotal'] ?? count($posts)),
            'posts' => $posts
        ];
        return $response;
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
        if (!$this->token) {
            Helper::debug("Access token is required. Call getAccessToken() first.");
        }

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

        Helper::debug("starterpack response:");
        Helper::debug($response);
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
        // Use a transient to cache for an hour
        $cacheKey = 'rrze_bluesky_starterpack_all_' . md5($starterPackUri);
        $cached   = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        // 1) Ensure we have an access token
        if (!$this->getAccessToken()) {
            Helper::debug("No valid Bluesky token to fetch starter pack data.");
            return null;
        }

        // 2) Convert bsky.app link -> at:// if needed
        if (!str_starts_with($starterPackUri, 'at://')) {
            $converted = $this->convertBskyStarterPackLinkToAtUri($starterPackUri);
            if (!$converted) {
                Helper::debug("Invalid or unresolvable starterPack URI/link: " . $starterPackUri);
                return null;
            }
            $starterPackUri = $converted;
        }

        // 3) Retrieve the Starter Pack info
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

        if ($this->token) {
            $args['headers']['Authorization'] = "Bearer {$this->token}";
        }

        if ($method === "POST" && $data) {
            $args['body'] = json_encode($data);
        }

        if ($method === "GET" && $data) {
            $queryString = http_build_query($data);
            $url .= '?' . $queryString;
        }

        $response = ($method === "POST")
            ? wp_remote_post($url, $args)
            : wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return $response; // WP_Error-Objekt zurückgeben
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Helper::debug('json_error', 'Failed to decode JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
