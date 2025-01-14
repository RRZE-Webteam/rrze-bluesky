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

        $data_encryption = new Encryption();
        $username = $data_encryption->decrypt(get_option('rrze_bluesky_username'));
        $password = $data_encryption->decrypt(get_option('rrze_bluesky_password'));

        if (!$username || !$password) {
            Helper::debug('Fehler: Benutzername oder Passwort fehlt in der .env-Datei.');
            return;
        }

        // Instanziere die API
        $api = new API($username, $password);

        // Authentifizieren
        $token = $api->getAccessToken();

        if (!$token) {
            Helper::debug('Fehler bei der Authentifizierung.');
        } else {
            Helper::debug('Erfolgreich authentifiziert. Token:', $token);
        }

        // Beispiel: Öffentliche Timeline abrufen
        $timeline = $api->getPublicTimeline();
        Helper::debug($timeline);
        $arrayToString = implode(", ", $timeline);

        if ($timeline) {
            Helper::debug('Öffentliche Timeline:', $arrayToString);
        } else {
            Helper::debug('Fehler beim Abrufen der öffentlichen Timeline.');
        }

        $list = $api->getList(['list' => 'at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.graph.list/3kfdow6lmdr27']);
        Helper::debug('Liste:');
        Helper::debug($list);
    }
}
