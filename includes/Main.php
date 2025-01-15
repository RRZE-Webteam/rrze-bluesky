<?php

namespace RRZE\Bluesky;

defined('ABSPATH') || exit;

use RRZE\Bluesky\API;

class Main
{
    public function __construct()
    {
        // Initialisiere Helper
        new Helper();
        new Settings();
        new Blocks();
        new Rest();
    }
}
