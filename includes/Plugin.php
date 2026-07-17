<?php

namespace RRZE\Bluesky;

defined('ABSPATH') || exit;

/**
 * Provides metadata and path/URL helpers for the plugin.
 *
 * Loaded via the main plugin file and used throughout the plugin
 * to resolve paths, URLs and the current version.
 */
class Plugin
{
    /**
     * The full path and filename of the plugin.
     * @var string
     */
    protected $pluginFile;

    /**
     * The basename of the plugin.
     * @var string
     */
    protected $basename;

    /**
     * The filesystem directory path (with trailing slash) for the plugin.
     * @var string
     */
    protected $directory;

    /**
     * The URL directory path (with trailing slash) for the plugin.
     * @var string
     */
    protected $url;

    /**
     * The version of the plugin.
     * @var string
     */
    protected $version;

    /**
     * @param string $pluginFile Absolute path to the main plugin file.
     */
    public function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
    }

  /**
   * Initialises all plugin metadata by chaining the setter methods.
   *
   * @return void
   */
  public function loaded(): void
    {
        $this->setBasename()
            ->setDirectory()
            ->setUrl()
            ->setVersion();
    }

    /**
     * Get the full path and filename of the plugin.
     *
     * @return string The full path and filename.
     */
    public function getFile(): string
    {
        return $this->pluginFile;
    }

    /**
     * Get the basename of the plugin.
     *
     * @return string The basename.
     */
    public function getBasename(): string
    {
        return $this->basename;
    }

    /**
     * Set the basename of the plugin.
     *
     * @return static This Plugin object.
     */
    public function setBasename(): static
    {
        $this->basename = plugin_basename($this->pluginFile);
        return $this;
    }

    /**
     * Get the filesystem directory path (with trailing slash) for the plugin.
     *
     * @return string The filesystem directory path.
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Set the filesystem directory path (with trailing slash) for the plugin.
     *
     * @return static This Plugin object.
     */
    public function setDirectory(): static
    {
        $this->directory = rtrim(plugin_dir_path($this->pluginFile), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * Get the filesystem directory path (with trailing slash) for the plugin.
     *
     * @param string $path The path name.
     * @return string The filesystem directory path.
     */
    public function getPath(string $path = ''): string
    {
        return $this->directory . ltrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Get the URL directory path (with trailing slash) for the plugin.
     *
     * @param string $path The path name.
     * @return string The URL directory path.
     */
    public function getUrl(string $path = ''): string
    {
        return $this->url . ltrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Set the URL directory path (with trailing slash) for the plugin.
     *
     * @return static This Plugin object.
     */
    public function setUrl(): static
    {
        $this->url = rtrim(plugin_dir_url($this->pluginFile), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * Get the slug of the plugin.
     *
     * @return string The slug.
     */
    public function getSlug(): string
    {
        return sanitize_title(dirname($this->basename));
    }

    /**
     * Get the version of the plugin.
     *
     * @return string The version.
     */
    public function getVersion(): string
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return bin2hex(random_bytes(4));
        }
        return $this->version;
    }

    /**
     * Set the version of the plugin.
     *
     * @return static This Plugin object.
     */
    public function setVersion(): static
    {
        $headers = ['Version' => 'Version'];
        $fileData = get_file_data($this->pluginFile, $headers, 'plugin');
        if (isset($fileData['Version'])) {
            $this->version = $fileData['Version'];
        };
        return $this;
    }

  /**
   * Handles calls to undefined methods by logging an error.
   *
   * @param string $name      The method name that was called.
   * @param array  $arguments The arguments passed to the method.
   * @return void
   */
  public function __call(string $name, array $arguments): void
    {
        if (!method_exists($this, $name)) {
            $message = sprintf(__('Call to undefined method %1$s::%2$s', 'rrze-bluesky'), __CLASS__, $name);
            do_action(
                'rrze.log.error',
                $message,
                [
                    'class' => __CLASS__,
                    'method' => $name,
                    'arguments' => $arguments
                ]
            );
            if (defined('WP_DEBUG') && WP_DEBUG) {
                throw new \Exception($message);
            }
        }
    }
}
