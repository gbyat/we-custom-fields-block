<?php

/**
 * Plugin Name: WE Custom Fields Block
 * Plugin URI: https://github.com/gbyat/we-custom-fields-block
 * Description: Fügt native WordPress Custom Fields als Blöcke mit Typografie- und Farboptionen ein
 * Version: 0.1.4
 * Author: Gabriele Laesser
 * License: GPL v2 or later
 * Text Domain: we-custom-fields-block
 * Domain Path: /languages
 * Update URI: https://github.com/gbyat/we-custom-fields-block/releases/latest/download/we-custom-fields-block.zip
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CFB_VERSION', '0.1.4');
define('CFB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFB_GITHUB_REPO', 'gbyat/we-custom-fields-block');

/**
 * Main plugin class
 */
class CustomFieldsBlock
{

    public function __init__()
    {
        // Editor assets for custom fields data
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));

        // Update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'upgrader_post_install'), 10, 3);

        // Admin settings - remove old menu first, then add new one
        add_action('admin_menu', array($this, 'remove_old_menu'), 5);
        add_action('admin_menu', array($this, 'add_admin_menu'), 10);
        add_action('admin_init', array($this, 'init_settings'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'set_block_script_translations'), 20);

        // Cache management
        add_action('save_post', array($this, 'clear_custom_fields_cache'));
        add_action('deleted_post', array($this, 'clear_custom_fields_cache'));
        add_action('updated_post_meta', array($this, 'clear_custom_fields_cache'));
        add_action('added_post_meta', array($this, 'clear_custom_fields_cache'));
        add_action('deleted_post_meta', array($this, 'clear_custom_fields_cache'));
    }

    /**
     * Load plugin translations
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'we-custom-fields-block',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Set script translations for block editor scripts
     */
    public function set_block_script_translations()
    {
        if (!function_exists('wp_set_script_translations')) {
            return;
        }

        $registry = \WP_Block_Type_Registry::get_instance();
        $block_type = $registry->get_registered('we-custom-fields-block/custom-field');

        if (!$block_type) {
            return;
        }

        $languages_path = plugin_dir_path(__FILE__) . 'languages';

        if (!empty($block_type->editor_script_handles)) {
            foreach ($block_type->editor_script_handles as $handle) {
                wp_set_script_translations($handle, 'we-custom-fields-block', $languages_path);
            }
            return;
        }

        $block_name_slug = str_replace('/', '-', 'we-custom-fields-block/custom-field');
        wp_set_script_translations(
            $block_name_slug . '-editor-script',
            'we-custom-fields-block',
            $languages_path
        );
    }

    /**
     * Remove old admin menu to prevent conflicts
     */
    public function remove_old_menu()
    {
        remove_submenu_page('options-general.php', 'we-custom-fields-block-settings');
    }

    /**
     * Check for plugin updates
     */
    public function check_for_updates($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $plugin_slug = basename(dirname(__FILE__));
        $plugin_file = basename(__FILE__);
        $plugin_path = $plugin_slug . '/' . $plugin_file;

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

        $plugin_slug = basename(dirname(__FILE__));

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
            'author' => 'Gabriele Lässer',
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
    private function get_latest_release()
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
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === basename(__FILE__)) {
            // Clear update cache
            delete_transient('cfb_latest_release');
        }

        return $response;
    }

    /**
     * Clear update cache manually
     */
    public function clear_update_cache()
    {
        delete_transient('cfb_latest_release');
        delete_site_transient('update_plugins');
        delete_site_transient('update_themes');
        delete_site_transient('update_core');

        // Force WordPress to check for updates immediately
        wp_schedule_single_event(time(), 'wp_version_check');
        wp_schedule_single_event(time(), 'wp_update_plugins');
        wp_schedule_single_event(time(), 'wp_update_themes');

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


    /**
     * Enqueue block editor assets
     * Only for passing custom fields data to the editor
     */
    public function enqueue_block_editor_assets()
    {
        // The script handle is generated from block name in block.json
        // Format: create_block_style_handle() uses the block name
        // For "we-custom-fields-block/custom-field" it becomes "we-custom-fields-block-custom-field"

        // Try different possible handles
        $possible_handles = array(
            'we-custom-fields-block-custom-field-editor-script',
            'we-custom-fields-block-custom-field-script',
            'we-custom-fields-block-editor',
        );

        foreach ($possible_handles as $handle) {
            if (wp_script_is($handle, 'registered')) {
                wp_localize_script($handle, 'cfbData', array(
                    'customFields' => $this->get_custom_fields(),
                    'nonce' => wp_create_nonce('cfb_nonce'),
                ));
                break;
            }
        }
    }

    /**
     * Get all custom fields for the current post
     */
    private function get_custom_fields()
    {
        // Try to get cached custom fields first
        $cached_fields = get_transient('cfb_all_custom_fields');

        if ($cached_fields !== false && !empty($cached_fields)) {
            return $cached_fields;
        }

        // If no cache or empty cache, build it
        $fields = $this->build_custom_fields_cache();

        // Cache for 1 hour
        set_transient('cfb_all_custom_fields', $fields, 3600);

        // If still no fields, try a simple fallback
        if (empty($fields)) {
            $fields = $this->get_fallback_custom_fields();
        }

        return $fields;
    }

    /**
     * Build cache of all custom fields from the site
     */
    private function build_custom_fields_cache()
    {
        global $wpdb;

        $fields = array();
        $field_keys = array();

        // Get all custom field keys from the database - less restrictive
        $meta_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT meta_key 
                FROM {$wpdb->postmeta} 
                WHERE meta_key NOT LIKE %s 
                AND meta_key NOT LIKE %s
                AND meta_key NOT LIKE %s
                AND meta_key NOT LIKE %s
                ORDER BY meta_key",
                '_edit_%', // Skip edit lock fields
                '_wp_%', // Skip WordPress internal fields
                'field_%', // Skip ACF internal fields
                '_thumbnail_id' // Skip featured image
            )
        );

        // Debug: Log what we found
        error_log('Custom Fields Block Debug: Found ' . count($meta_keys) . ' meta keys: ' . print_r($meta_keys, true));

        if (empty($meta_keys)) {
            // Try even less restrictive query
            $meta_keys = $wpdb->get_col(
                "SELECT DISTINCT meta_key 
                FROM {$wpdb->postmeta} 
                WHERE meta_key NOT LIKE '_edit_%'
                ORDER BY meta_key"
            );
            error_log('Custom Fields Block Debug: Less restrictive query found ' . count($meta_keys) . ' meta keys');
        }

        if (empty($meta_keys)) {
            error_log('Custom Fields Block Debug: No meta keys found at all');
            return array();
        }

        // Get sample values for each field
        foreach ($meta_keys as $meta_key) {
            // Get a sample value from the most recent post with this field
            $sample_value = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value 
                    FROM {$wpdb->postmeta} pm
                    JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                    WHERE pm.meta_key = %s 
                    AND p.post_status = 'publish'
                    ORDER BY p.post_date DESC
                    LIMIT 1",
                    $meta_key
                )
            );

            // Also try without post status restriction
            if (!$sample_value) {
                $sample_value = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_value 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key = %s 
                        ORDER BY meta_id DESC
                        LIMIT 1",
                        $meta_key
                    )
                );
            }

            if ($sample_value) {
                $fields[] = array(
                    'key' => $meta_key,
                    'label' => $this->format_field_name($meta_key),
                    'value' => $sample_value,
                );
            }
        }

        error_log('Custom Fields Block Debug: Final fields array: ' . print_r($fields, true));
        return $fields;
    }

    /**
     * Fallback method to get custom fields if cache is empty
     */
    private function get_fallback_custom_fields()
    {
        global $wpdb;

        error_log('Custom Fields Block Debug: Using fallback method');

        // Method 1: Get from recent posts with custom fields
        $recent_posts = get_posts(array(
            'numberposts' => 20,
            'post_status' => 'publish',
        ));

        $fields = array();
        $seen_keys = array();

        foreach ($recent_posts as $post) {
            $custom_fields = get_post_custom($post->ID);

            foreach ($custom_fields as $key => $values) {
                // Skip internal WordPress fields
                if (strpos($key, '_') === 0) {
                    continue;
                }

                // Skip if we already have this field
                if (in_array($key, $seen_keys)) {
                    continue;
                }

                $seen_keys[] = $key;
                $value = is_array($values) ? $values[0] : $values;

                $fields[] = array(
                    'key' => $key,
                    'label' => $this->format_field_name($key) . ' (from post: ' . $post->post_title . ')',
                    'value' => $value,
                );
            }
        }

        // Method 2: Direct database query if still no fields
        if (empty($fields)) {
            error_log('Custom Fields Block Debug: Trying direct database query');

            $meta_keys = $wpdb->get_col(
                "SELECT DISTINCT meta_key 
                FROM {$wpdb->postmeta} 
                WHERE meta_key NOT LIKE '_%'
                LIMIT 50"
            );

            foreach ($meta_keys as $meta_key) {
                $sample_value = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1",
                        $meta_key
                    )
                );

                if ($sample_value) {
                    $fields[] = array(
                        'key' => $meta_key,
                        'label' => $this->format_field_name($meta_key),
                        'value' => $sample_value,
                    );
                }
            }
        }

        error_log('Custom Fields Block Debug: Fallback found ' . count($fields) . ' fields');
        return $fields;
    }

    /**
     * Clear custom fields cache
     */
    public function clear_custom_fields_cache()
    {
        delete_transient('cfb_all_custom_fields');
    }

    /**
     * Format field name for display
     */
    private function format_field_name($key)
    {
        return ucwords(str_replace(array('_', '-'), ' ', $key));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_options_page(
            'Custom Fields Block',
            'Custom Fields Block',
            'manage_options',
            'we-custom-fields-block',
            array($this, 'admin_page')
        );
    }

    /**
     * Initialize settings
     */
    public function init_settings()
    {
        register_setting('cfb_settings', 'cfb_github_token');
        register_setting('cfb_settings', 'cfb_custom_fields_cache', array(
            'type' => 'array',
            'default' => array()
        ));

        add_settings_section(
            'cfb_github_section',
            'GitHub Update Settings',
            array($this, 'github_section_callback'),
            'cfb_settings'
        );

        add_settings_field(
            'cfb_github_token',
            'GitHub Personal Access Token',
            array($this, 'github_token_callback'),
            'cfb_settings',
            'cfb_github_section'
        );
    }

    /**
     * GitHub section callback
     */
    public function github_section_callback()
    {
        echo '<p>Configure GitHub integration for automatic plugin updates.</p>';
    }

    /**
     * GitHub token callback
     */
    public function github_token_callback()
    {
        $token = get_option('cfb_github_token');
        echo '<input type="password" name="cfb_github_token" value="' . esc_attr($token) . '" class="regular-text" />';
        echo '<p class="description">Enter your GitHub Personal Access Token for automatic updates.</p>';
    }

    /**
     * Main admin page with tabs
     */
    public function admin_page()
    {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'custom-fields';

        // Handle form submissions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'scan_custom_fields':
                    $this->scan_custom_fields();
                    break;
                case 'clear_cache':
                    $this->clear_custom_fields_cache();
                    break;
                case 'clear_update_cache':
                    $this->clear_update_cache();
                    break;
                case 'save_settings':
                    $this->save_settings();
                    break;
            }
        }
?>
        <div class="wrap">
            <h1>Custom Fields Block</h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=we-custom-fields-block&tab=custom-fields"
                    class="nav-tab <?php echo $active_tab === 'custom-fields' ? 'nav-tab-active' : ''; ?>">
                    Custom Fields Manager
                </a>
                <a href="?page=we-custom-fields-block&tab=settings"
                    class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    Settings
                </a>
                <a href="?page=we-custom-fields-block&tab=debug"
                    class="nav-tab <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
                    Debug Info
                </a>
            </nav>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'custom-fields':
                        $this->custom_fields_tab();
                        break;
                    case 'settings':
                        $this->settings_tab();
                        break;
                    case 'debug':
                        $this->debug_tab();
                        break;
                }
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Custom Fields tab
     */
    private function custom_fields_tab()
    {
        $custom_fields = $this->get_custom_fields();
    ?>
        <div class="tab-pane">
            <h2>Custom Fields Manager</h2>

            <div class="cfb-actions">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="scan_custom_fields">
                    <?php wp_nonce_field('cfb_scan_fields', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-primary">
                        🔍 Scan Custom Fields
                    </button>
                </form>

                <form method="post" style="display: inline; margin-left: 10px;">
                    <input type="hidden" name="action" value="clear_cache">
                    <?php wp_nonce_field('cfb_clear_cache', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-secondary">
                        🗑️ Clear Cache
                    </button>
                </form>
            </div>

            <div class="cfb-stats">
                <p><strong>Found <?php echo count($custom_fields); ?> custom fields</strong></p>
            </div>

            <?php if (!empty($custom_fields)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Field Key</th>
                            <th>Display Name</th>
                            <th>Sample Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($custom_fields as $field): ?>
                            <tr>
                                <td><code><?php echo esc_html($field['key']); ?></code></td>
                                <td><?php echo esc_html($field['label']); ?></td>
                                <td>
                                    <span class="cfb-sample-value">
                                        <?php echo esc_html(substr($field['value'], 0, 50)); ?>
                                        <?php if (strlen($field['value']) > 50): ?>...<?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="button button-small" onclick="copyToClipboard('<?php echo esc_js($field['key']); ?>')">
                                        Copy Key
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p>No custom fields found. Click "Scan Custom Fields" to search for custom fields in your posts.</p>
                </div>
            <?php endif; ?>
        </div>

        <script>
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Field key copied to clipboard: ' + text);
                });
            }
        </script>
    <?php
    }

    /**
     * Settings tab
     */
    private function settings_tab()
    {
    ?>
        <div class="tab-pane">
            <h2>Settings</h2>

            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <?php wp_nonce_field('cfb_save_settings', 'cfb_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">GitHub Token</th>
                        <td>
                            <input type="password" name="cfb_github_token"
                                value="<?php echo esc_attr(get_option('cfb_github_token')); ?>"
                                class="regular-text" />
                            <p class="description">Personal Access Token for automatic plugin updates</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
    <?php
    }

    /**
     * Debug tab
     */
    private function debug_tab()
    {
        $latest_release = $this->get_latest_release();
        $custom_fields = $this->get_custom_fields();
        $github_token = get_option('cfb_github_token');
    ?>
        <div class="tab-pane">
            <h2>Debug Information</h2>

            <h3>Update System</h3>
            <table class="form-table">
                <tr>
                    <th>Current Version</th>
                    <td><strong><?php echo CFB_VERSION; ?></strong></td>
                </tr>
                <tr>
                    <th>Latest Version</th>
                    <td>
                        <?php if ($latest_release): ?>
                            <strong><?php echo esc_html($latest_release['version']); ?></strong>
                            <?php if (version_compare($latest_release['version'], CFB_VERSION, '>')): ?>
                                <span style="color: green;">✅ Update available!</span>
                            <?php else: ?>
                                <span style="color: blue;">✅ Up to date</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: red;">❌ Could not fetch latest release</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>GitHub Token</th>
                    <td>
                        <?php if ($github_token): ?>
                            <span style="color: green;">✅ Set (<?php echo substr($github_token, 0, 8) . '...'; ?>)</span>
                        <?php else: ?>
                            <span style="color: red;">❌ Not set</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>GitHub API Test</th>
                    <td>
                        <?php
                        try {
                            $api_url = 'https://api.github.com/repos/' . CFB_GITHUB_REPO . '/releases/latest';
                            $headers = array(
                                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                                'Accept' => 'application/vnd.github.v3+json'
                            );

                            if (!empty($github_token)) {
                                $headers['Authorization'] = 'token ' . $github_token;
                            }

                            $response = wp_remote_get($api_url, array(
                                'headers' => $headers,
                                'timeout' => 15
                            ));

                            if (is_wp_error($response)) {
                                echo '<span style="color: red;">❌ Error: ' . esc_html($response->get_error_message()) . '</span>';
                            } else {
                                $response_code = wp_remote_retrieve_response_code($response);
                                echo '<span style="color: blue;">Response Code: ' . esc_html($response_code) . '</span>';

                                if ($response_code === 200) {
                                    $body = wp_remote_retrieve_body($response);
                                    $release = json_decode($body, true);

                                    if ($release && isset($release['tag_name'])) {
                                        echo '<br><strong>Latest Release:</strong> ' . esc_html($release['tag_name']);

                                        // Check for ZIP asset
                                        $has_zip = false;
                                        if (isset($release['assets']) && is_array($release['assets'])) {
                                            foreach ($release['assets'] as $asset) {
                                                if (isset($asset['name']) && $asset['name'] === 'we-custom-fields-block.zip') {
                                                    $has_zip = true;
                                                    break;
                                                }
                                            }
                                        }
                                        echo '<br><strong>ZIP Asset:</strong> ' . ($has_zip ? '✅ Present' : '❌ Missing');
                                    } else {
                                        echo '<br><span style="color: red;">❌ Could not parse release data</span>';
                                    }
                                } else {
                                    echo '<br><span style="color: red;">❌ API Error: ' . esc_html($response_code) . '</span>';
                                }
                            }
                        } catch (Exception $e) {
                            echo '<span style="color: red;">❌ Exception: ' . esc_html($e->getMessage()) . '</span>';
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <h3>Custom Fields Cache</h3>
            <table class="form-table">
                <tr>
                    <th>Cached Fields</th>
                    <td><?php echo count($custom_fields); ?> fields</td>
                </tr>
                <tr>
                    <th>Cache Status</th>
                    <td>
                        <?php
                        $cache_data = get_transient('cfb_all_custom_fields');
                        if ($cache_data): ?>
                            <span style="color: green;">✅ Active (<?php echo count($cache_data); ?> fields)</span>
                        <?php else: ?>
                            <span style="color: red;">❌ Empty</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Update Cache</th>
                    <td>
                        <?php
                        $update_cache = get_transient('cfb_latest_release');
                        if ($update_cache): ?>
                            <span style="color: green;">✅ Active</span>
                        <?php else: ?>
                            <span style="color: red;">❌ Empty</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h3>System Information</h3>
            <table class="form-table">
                <tr>
                    <th>WordPress Version</th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th>PHP Version</th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <th>Plugin Directory</th>
                    <td><code><?php echo CFB_PLUGIN_DIR; ?></code></td>
                </tr>
                <tr>
                    <th>Plugin URL</th>
                    <td><code><?php echo CFB_PLUGIN_URL; ?></code></td>
                </tr>
                <tr>
                    <th>GitHub Repository</th>
                    <td><code><?php echo CFB_GITHUB_REPO; ?></code></td>
                </tr>
            </table>

            <h3>Cache Management</h3>
            <div class="cfb-actions">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="clear_update_cache">
                    <?php wp_nonce_field('cfb_clear_update_cache', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-secondary">
                        🗑️ Clear Update Cache
                    </button>
                </form>

                <form method="post" style="display: inline; margin-left: 10px;">
                    <input type="hidden" name="action" value="clear_cache">
                    <?php wp_nonce_field('cfb_clear_cache', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-secondary">
                        🗑️ Clear Custom Fields Cache
                    </button>
                </form>

                <a href="<?php echo admin_url('update-core.php'); ?>" class="button button-primary" style="margin-left: 10px;">
                    🔄 Check for Updates
                </a>
            </div>

            <h3>Help</h3>
            <div class="card">
                <h4>How to get a GitHub Token:</h4>
                <ol>
                    <li>Go to <a href="https://github.com/settings/tokens" target="_blank">GitHub Settings → Developer settings → Personal access tokens</a></li>
                    <li>Click "Generate new token (classic)"</li>
                    <li>Give it a name like "WordPress Plugin Updates"</li>
                    <li>Select scopes: <code>repo</code> and <code>workflow</code></li>
                    <li>Generate and copy the token</li>
                    <li>Paste it in the Settings tab and save</li>
                </ol>

                <h4>Creating a Release:</h4>
                <ol>
                    <li>Make your changes and commit them</li>
                    <li>Run: <code>npm run release:patch</code> (or minor/major)</li>
                    <li>GitHub will automatically create a release with the plugin ZIP</li>
                    <li>WordPress will detect the update and show it in the admin</li>
                </ol>
            </div>
        </div>
<?php
    }

    /**
     * Scan custom fields
     */
    private function scan_custom_fields()
    {
        if (!wp_verify_nonce($_POST['cfb_nonce'], 'cfb_scan_fields')) {
            wp_die('Security check failed');
        }

        // Clear existing cache
        $this->clear_custom_fields_cache();

        // Force new scan
        $fields = $this->build_custom_fields_cache();

        // Cache the results
        set_transient('cfb_all_custom_fields', $fields, 3600);

        add_action('admin_notices', function () use ($fields) {
            echo '<div class="notice notice-success"><p>Found ' . count($fields) . ' custom fields!</p></div>';
        });
    }

    /**
     * Save settings
     */
    private function save_settings()
    {
        if (!wp_verify_nonce($_POST['cfb_nonce'], 'cfb_save_settings')) {
            wp_die('Security check failed');
        }

        if (isset($_POST['cfb_github_token'])) {
            update_option('cfb_github_token', sanitize_text_field($_POST['cfb_github_token']));
        }

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        });
    }
}

// Initialize the plugin
$custom_fields_block = new CustomFieldsBlock();
$custom_fields_block->__init__();

// Register block directly in init hook
add_action('init', function () {
    register_block_type(CFB_PLUGIN_DIR . '/build', array(
        'render_callback' => 'cfb_render_block'
    ));
});

// Render callback function
function cfb_render_block($attributes, $content, $block)
{
    // Get block attributes
    $field_key = isset($attributes['fieldKey']) ? $attributes['fieldKey'] : '';
    $display_type = isset($attributes['displayType']) ? $attributes['displayType'] : 'paragraph';
    $heading_level = isset($attributes['headingLevel']) ? intval($attributes['headingLevel']) : 2;

    // Return early if no field key
    if (empty($field_key)) {
        return '';
    }

    // Get field value
    $field_value = get_post_meta(get_the_ID(), $field_key, true);

    // Return early if no value
    if (empty($field_value)) {
        return '';
    }

    // Get block wrapper attributes (includes all theme styles)
    $wrapper_attributes = get_block_wrapper_attributes(array(
        'class' => 'cfb-block'
    ));

    // Render based on display type
    switch ($display_type) {
        case 'heading':
            // Validate heading level (1-6)
            $heading_level = max(1, min(6, $heading_level));
            $tag = 'h' . $heading_level;

            return sprintf(
                '<%1$s %2$s>%3$s</%1$s>',
                $tag,
                $wrapper_attributes,
                esc_html($field_value)
            );

        case 'div':
            return sprintf(
                '<div %s>%s</div>',
                $wrapper_attributes,
                esc_html($field_value)
            );

        case 'paragraph':
        default:
            return sprintf(
                '<p %s>%s</p>',
                $wrapper_attributes,
                esc_html($field_value)
            );
    }
}
