<?php
/**
 * GitHub Plugin Updater for Simple Event
 *
 * Handles automatic plugin updates from GitHub releases.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SE_GitHub_Updater {

    private $slug;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_response;
    private $access_token;
    private $cache_key = 'se_github_updater_response';

    /**
     * Constructor
     *
     * @param string $plugin_file Full path to the main plugin file
     */
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->slug = plugin_basename($plugin_file);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_source_selection', [$this, 'rename_source_folder'], 10, 4);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

    /**
     * Set GitHub repository info
     *
     * @param string $username GitHub username
     * @param string $repo Repository name
     */
    public function set_repository($username, $repo) {
        $this->username = $username;
        $this->repo = $repo;
    }

    /**
     * Set access token for private repos
     *
     * @param string $token GitHub access token
     */
    public function set_access_token($token) {
        $this->access_token = $token;
    }

    /**
     * Get plugin data
     */
    private function get_plugin_data() {
        if (empty($this->plugin_data)) {
            $this->plugin_data = get_plugin_data($this->plugin_file);
        }
        return $this->plugin_data;
    }

    /**
     * Get GitHub release info (with transient cache)
     */
    private function get_github_release() {
        if (!empty($this->github_response)) {
            return $this->github_response;
        }

        // Check transient cache first (cache for 6 hours)
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            $this->github_response = $cached;
            return $this->github_response;
        }

        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";

        $args = [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            ]
        ];

        if (!empty($this->access_token)) {
            $args['headers']['Authorization'] = "token {$this->access_token}";
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        if (empty($body) || !isset($body->tag_name)) {
            return false;
        }

        $this->github_response = $body;

        // Cache for 6 hours to avoid rate limiting
        set_transient($this->cache_key, $body, 6 * HOUR_IN_SECONDS);

        return $this->github_response;
    }

    /**
     * Check for plugin updates
     *
     * @param object $transient Update transient
     * @return object Modified transient
     */
    public function check_update($transient) {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        $release = $this->get_github_release();

        if (!$release) {
            return $transient;
        }

        $plugin_data = $this->get_plugin_data();
        $current_version = $plugin_data['Version'];

        // Remove 'v' prefix from tag if present
        $latest_version = ltrim($release->tag_name, 'v');

        if (version_compare($latest_version, $current_version, '>')) {
            // Find the zip asset
            $download_url = $release->zipball_url;

            // Check if there's a specific zip asset attached
            if (!empty($release->assets)) {
                foreach ($release->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }

            $icon_url = plugin_dir_url($this->plugin_file) . 'assets/icon.svg';
            $transient->response[$this->slug] = (object) [
                'slug' => dirname($this->slug),
                'plugin' => $this->slug,
                'new_version' => $latest_version,
                'url' => $release->html_url,
                'package' => $download_url,
                'icons' => [
                    'svg' => $icon_url,
                    'default' => $icon_url,
                ],
                'banners' => [],
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
            ];
        } else {
            // No update needed
            unset($transient->response[$this->slug]);
        }

        return $transient;
    }

    /**
     * Plugin information for the update details popup
     *
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return object|false
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (dirname($this->slug) !== $args->slug) {
            return $result;
        }

        $release = $this->get_github_release();

        if (!$release) {
            return $result;
        }

        $plugin_data = $this->get_plugin_data();

        // Find download URL (prefer zip asset)
        $download_url = $release->zipball_url;
        if (!empty($release->assets)) {
            foreach ($release->assets as $asset) {
                if (strpos($asset->name, '.zip') !== false) {
                    $download_url = $asset->browser_download_url;
                    break;
                }
            }
        }

        $icon_url = plugin_dir_url($this->plugin_file) . 'assets/icon.svg';
        return (object) [
            'name' => $plugin_data['Name'],
            'slug' => dirname($this->slug),
            'version' => ltrim($release->tag_name, 'v'),
            'author' => $plugin_data['AuthorName'],
            'homepage' => $plugin_data['PluginURI'],
            'short_description' => $plugin_data['Description'],
            'sections' => [
                'description' => $plugin_data['Description'],
                'changelog' => $this->parse_changelog($release->body),
            ],
            'icons' => [
                'svg' => $icon_url,
                'default' => $icon_url,
            ],
            'download_link' => $download_url,
            'last_updated' => $release->published_at,
            'tested' => get_bloginfo('version'),
            'requires_php' => '7.4',
        ];
    }

    /**
     * Parse markdown changelog to HTML
     *
     * @param string $body Release body/notes
     * @return string HTML formatted changelog
     */
    private function parse_changelog($body) {
        if (empty($body)) {
            return '<p>No changelog provided.</p>';
        }

        $body = esc_html($body);
        $body = nl2br($body);
        $body = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $body);
        $body = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $body);
        $body = preg_replace('/^- (.+)$/m', '<li>$1</li>', $body);
        $body = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $body);

        return $body;
    }

    /**
     * Rename extracted source folder to match plugin folder name.
     * This handles both auto-update and manual ZIP upload scenarios.
     *
     * @param string $source        Extracted source path
     * @param string $remote_source Remote source path
     * @param object $upgrader      WP_Upgrader instance
     * @param array  $hook_extra    Extra arguments
     * @return string|WP_Error
     */
    public function rename_source_folder($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;

        $plugin_dir_name = dirname($this->slug); // e.g. "simple-event"

        // Check if this is our plugin (auto-update has plugin key)
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->slug) {
            // Auto-update flow — rename folder
        } else {
            // Manual upload — check if the extracted folder contains our main plugin file
            $source_plugin_file = trailingslashit($source) . basename($this->plugin_file);
            if (!$wp_filesystem->exists($source_plugin_file)) {
                return $source; // Not our plugin, skip
            }
        }

        $corrected_source = trailingslashit($remote_source) . $plugin_dir_name . '/';

        if ($source !== $corrected_source) {
            $wp_filesystem->move($source, $corrected_source);
        }

        return $corrected_source;
    }

    /**
     * Post-install cleanup: clear cache and re-activate if needed.
     *
     * @param bool $response
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function after_install($response, $hook_extra, $result) {
        // Clear updater cache after install
        delete_transient($this->cache_key);

        // Re-activate plugin if it was active
        if (is_plugin_active($this->slug)) {
            activate_plugin($this->slug);
        }

        return $result;
    }
}
