<?php


namespace RRZE\Bluesky;

/**
 * Loads and provides access to plugin configuration from a JSON file.
 */
class Config {
    private array $config;

    /**
     * Config-Konstruktor
     * Lädt die Konfigurationswerte aus der JSON-Datei
     *
     * @param string $filePath Der Pfad zur JSON-Datei
     * @throws \Exception Wenn die Datei nicht gelesen oder die JSON-Daten ungültig sind
     */
     public function __construct(string $filePath = __DIR__ . '/../config.json') {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Konfigurationsdatei nicht gefunden: $filePath");
        }

        $jsonData = file_get_contents($filePath);
        $this->config = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Fehler beim Parsen der JSON-Datei: " . json_last_error_msg());
        }
    }
    /**
     * Abrufen eines Konfigurationswertes
     *
     * @param string $key Der Schlüssel des Konfigurationswertes
     * @return mixed|null Der Wert oder null, wenn der Schlüssel nicht existiert
     */
    public function get(string $key): mixed {
        return $this->config[$key] ?? null;
    }


    /**
     * Alle Konfigurationswerte abrufen
     *
     * @return array Das gesamte Konfigurationsarray
     */
    public function getAll(): array {
        return $this->config;
    }
}
