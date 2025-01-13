<?php

namespace RRZE\Bluesky;

defined('ABSPATH') || exit;

class REST
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Register the different routes for the REST API
     */
    public function registerRestRoutes()
    {
        // register_rest_route('rrze-bluesky/v1', '/public-timeline', [
        //     'methods' => 'GET',
        //     'callback' => [$this, 'getPublicTimeline'],
        // ]);

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
}