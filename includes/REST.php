<?php

namespace RRZE\Bluesky;

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
        ]);

        // register_rest_route('rrze_bluesky/v1', '/list', [
        //     'methods' => 'GET',
        //     'callback' => [$this, 'getList'],
        // ]);

        // register_rest_route('rrze_bluesky/v1', '/user', [
        //     'methods' => 'GET',
        //     'callback' => [$this, 'getUserProfile'],
        // ]);

        // register_rest_route('rrze_bluesky/v1', '/post', [
        //     'methods' => 'GET',
        //     'callback' => [$this, 'getPost'],
        // ]);
    }

    /**
     * Get the public timeline
     */
    public function getPublicTimeline()
    {
        $api = $this->getApi();
        $token = $api->getAccessToken();
        if (!$token) {
            Helper::debug('Fehler bei der Authentifizierung.');
        } else {
            Helper::debug('Erfolgreich authentifiziert. Token:', $token);
        }

        $timeline = $api->getPublicTimeline();
        return $timeline;
    }
}
