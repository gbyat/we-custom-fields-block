<?php

/**
 * Update System Handler
 *
 * @package WE_Custom_Fields_Block
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFB_Updates
{
    /**
     * Check for plugin updates
     */
    public function check_for_updates($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $plugin_slug = basename(CFB_PLUGIN_DIR);
        $plugin_file = 'we-custom-fields-block.php';
        $plugin_path = $plugin_slug . '/' . $plugin_file;

        // Remove old plugin entries (from custom-fields-block)
        $old_paths = array(
            'custom-fields-block/custom-fields-block.php',
            'custom-fields-block/we-custom-fields-block.php'
        );
        foreach ($old_paths as $old_path) {
            if (isset($transient->response[$old_path])) {
                unset($transient->response[$old_path]);
            }
        }

        // Get latest release info from GitHub
        $latest_release = $this->get_latest_release();

        if ($latest_release && version_compare($latest_release['version'], CFB_VERSION, '>')) {
            $transient->response[$plugin_path] = (object) array(
                'slug' => $plugin_slug,
                'new_version' => $latest_release['version'],
                'url' => 'https://github.com/' . CFB_GITHUB_REPO,
                'package' => $latest_release['download_url'],
                'requires' => '5.0',
                'requires_php' => '7.4',
                'tested' => '6.4',
                'last_updated' => $latest_release['published_at'],
                'sections' => array(
                    'description' => $latest_release['description'],
                    'changelog' => $latest_release['changelog']
                )
            );
        }

        return $transient;
    }

    /**
     * Get plugin information for update screen
     */
    public function plugin_info($result, $action, $args)
    {
        if ($action !== 'plugin_information') {
            return $result;
        }

        $plugin_slug = basename(CFB_PLUGIN_DIR);

        if ($args->slug !== $plugin_slug) {
            return $result;
        }

        $latest_release = $this->get_latest_release();

        if (!$latest_release) {
            return $result;
        }

        return (object) array(
            'name' => 'Custom Fields Block',
            'slug' => $plugin_slug,
            'version' => $latest_release['version'],
            'author' => 'Gabriele Laesser',
            'author_profile' => 'https://github.com/gbyat',
            'last_updated' => $latest_release['published_at'],
            'requires' => '5.0',
            'requires_php' => '7.4',
            'tested' => '6.4',
            'download_link' => $latest_release['download_url'],
            'sections' => array(
                'description' => $latest_release['description'],
                'changelog' => $latest_release['changelog'],
                'installation' => 'Upload the plugin files to the /wp-content/plugins/we-custom-fields-block directory, or install the plugin through the WordPress plugins screen directly.',
                'screenshots' => ''
            )
        );
    }

    /**
     * Get latest release from GitHub
     */
    public function get_latest_release()
    {
        $cache_key = 'cfb_latest_release';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $api_url = 'https://api.github.com/repos/' . CFB_GITHUB_REPO . '/releases/latest';

        $headers = array(
            'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            'Accept' => 'application/vnd.github.v3+json'
        );

        // Token aus den Plugin-Optionen holen
        $github_token = get_option('cfb_github_token', '');
        if (!empty($github_token)) {
            $headers['Authorization'] = 'token ' . $github_token;
        }

        $response = wp_remote_get($api_url, array(
            'headers' => $headers,
            'timeout' => 15
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body, true);

        if (!$release) {
            return false;
        }

        // Find the plugin zip file
        $download_url = '';
        foreach ($release['assets'] as $asset) {
            if ($asset['name'] === 'we-custom-fields-block.zip') {
                $download_url = $asset['browser_download_url'];
                break;
            }
        }

        $release_data = array(
            'version' => ltrim($release['tag_name'], 'v'),
            'download_url' => $download_url,
            'published_at' => $release['published_at'],
            'description' => $release['body'],
            'changelog' => $release['body']
        );

        // Cache for 12 hours
        set_transient($cache_key, $release_data, 12 * 3600);

        return $release_data;
    }

    /**
     * Handle post-installation
     */
    public function upgrader_post_install($response, $hook_extra, $result)
    {
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === 'we-custom-fields-block.php') {
            // Clear update cache
            delete_transient('cfb_latest_release');
        }

        return $response;
    }

    /**
     * Clear update cache manually - only for this plugin
     */
    public function clear_update_cache()
    {
        // Clear plugin-specific cache
        delete_transient('cfb_latest_release');

        // Remove only this plugin from update_plugins transient
        $plugin_slug = basename(CFB_PLUGIN_DIR);
        $plugin_file = 'we-custom-fields-block.php';
        $plugin_path = $plugin_slug . '/' . $plugin_file;

        $update_plugins = get_site_transient('update_plugins');
        if ($update_plugins && is_object($update_plugins)) {
            if (isset($update_plugins->response[$plugin_path])) {
                unset($update_plugins->response[$plugin_path]);
                set_site_transient('update_plugins', $update_plugins);
            }
            // Also check for old plugin name (custom-fields-block)
            $old_plugin_path = 'custom-fields-block/custom-fields-block.php';
            if (isset($update_plugins->response[$old_plugin_path])) {
                unset($update_plugins->response[$old_plugin_path]);
                set_site_transient('update_plugins', $update_plugins);
            }
        }

        // Clear any cached plugin data
        wp_cache_flush();
    }

    /**
     * Debug function to check latest release
     */
    public function debug_latest_release()
    {
        $latest = $this->get_latest_release();
        if ($latest) {
            error_log('Custom Fields Block Debug - Latest Release: ' . print_r($latest, true));
        } else {
            error_log('Custom Fields Block Debug - No latest release found');
        }
    }
}
