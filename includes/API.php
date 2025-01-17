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

        $storedRefreshToken = get_transient('rrze_bluesky_refresh_token');
        if ($storedRefreshToken) {
            $this->refreshToken = $storedRefreshToken;
            Helper::debug('Loaded refresh token from transient.');
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
            // Store refresh token in transient
            // * If the server provides an expiration time for refresh tokens, use it!
            // * Otherwise, store it for a default (e.g. 1 day).
            $expirationInSeconds = 1200;
            set_transient(
                'rrze_bluesky_refresh_token',
                $this->refreshToken,
                $expirationInSeconds
            );

            Helper::debug ("Access token retrieved successfully.");
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

        // Werte aus der Config laden
        // $configParams = $this->config->get('query_getlist') ?? [];

        // Fehlende Werte aus der Config ergänzen
        // foreach ($configParams as $key => $value) {
        //     if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
        //         $search[$key] = $value;
        //     }
        // }
        // API-Anfrage
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
     * @return array|null List or null on not found
     */
    public function getProfile(array $search): ?Profil
    {
        if (!$this->token) {
            Helper::debug("Access token is required. Call getAccessToken() first.");
        }
        if (empty($search['actor'])) {
            Helper::debug('Required field actor (Handle or DID of account to fetch profile of.) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.actor.getProfile";

        $response = $this->makeRequest($url, "GET", $search);

        if (!$response) {
            error_log("Keine Antwort vom Server.");
            return null;
        }

        // Wandelt das Array in ein Profil-Objekt um
        return new Profil($response, $this->config);
    }

    /*
     * Suchanfrage
     */
    public function searchPosts(array $search): ?array
    {
        // Basis-URL des Endpoints
        $endpoint = "{$this->baseUrl}/app.bsky.feed.searchPosts";

        // Sicherstellen, dass das Feld 'q' vorhanden ist
        if (empty($search['q'])) {
            throw new \InvalidArgumentException('Required field q for the search string is missing.');
        }

        // Werte aus der Config laden
        $configParams = $this->config->get('query_searchPosts') ?? [];

        // Fehlende Werte aus der Config ergänzen
        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }

        // GET-Anfrage mit den Suchparametern
        $response = $this->makeRequest($endpoint, 'GET', $search);

        if (!$response) {
            error_log('No results found.');
            return null;
        }
        // Validieren der API-Antwort
        if (!isset($response['posts'])) {
            throw new \RuntimeException('Invalid api response');
        }

        // Umwandeln der Post-Daten in Post-Objekte
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
     * Führt eine HTTP-Anfrage aus.
     *
     * @param string $url Die URL für die Anfrage.
     * @param string $method Die HTTP-Methode ("GET" oder "POST").
     * @param array|null $data Optional: Daten für POST-Anfragen oder Query-Parameter für GET.
     * @return array|WP_Error Die JSON-Antwort als Array oder ein WP_Error bei Fehlern.
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
