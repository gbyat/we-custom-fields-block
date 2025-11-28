<?php

/**
 * Admin Interface Handler
 *
 * @package WE_Custom_Fields_Block
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFB_Admin
{
    private $custom_fields;
    private $updates;

    public function __construct($custom_fields, $updates)
    {
        $this->custom_fields = $custom_fields;
        $this->updates = $updates;
    }

    /**
     * Remove old admin menu to prevent conflicts
     */
    public function remove_old_menu()
    {
        remove_submenu_page('options-general.php', 'we-custom-fields-block-settings');
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_options_page(
            __('Custom Fields Block', 'we-custom-fields-block'),
            __('Custom Fields Block', 'we-custom-fields-block'),
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
            __('GitHub Update Settings', 'we-custom-fields-block'),
            array($this, 'github_section_callback'),
            'cfb_settings'
        );

        add_settings_field(
            'cfb_github_token',
            __('GitHub Personal Access Token', 'we-custom-fields-block'),
            array($this, 'github_token_callback'),
            'cfb_settings',
            'cfb_github_section'
        );

        // Register AJAX handler for auto-saving excluded fields
        add_action('wp_ajax_cfb_save_excluded_fields_ajax', array($this, 'ajax_save_excluded_fields'));
    }

    /**
     * GitHub section callback
     */
    public function github_section_callback()
    {
        echo '<p>' . esc_html__('Configure GitHub integration for automatic plugin updates.', 'we-custom-fields-block') . '</p>';
    }

    /**
     * GitHub token callback
     */
    public function github_token_callback()
    {
        $token = get_option('cfb_github_token');
        echo '<input type="password" name="cfb_github_token" value="' . esc_attr($token) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Enter your GitHub Personal Access Token for automatic updates.', 'we-custom-fields-block') . '</p>';
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
                    $this->custom_fields->scan_custom_fields();
                    break;
                case 'clear_cache':
                    $this->custom_fields->clear_custom_fields_cache();
                    break;
                case 'clear_update_cache':
                    $this->updates->clear_update_cache();
                    break;
                case 'save_settings':
                    $this->save_settings();
                    break;
                case 'delete_custom_fields':
                    $this->custom_fields->delete_custom_fields();
                    break;
                case 'save_excluded_fields':
                    $this->save_excluded_fields();
                    break;
            }
        }
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Custom Fields Block', 'we-custom-fields-block'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=we-custom-fields-block&tab=custom-fields"
                    class="nav-tab <?php echo $active_tab === 'custom-fields' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Custom Fields Manager', 'we-custom-fields-block'); ?>
                </a>
                <a href="?page=we-custom-fields-block&tab=settings"
                    class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Settings', 'we-custom-fields-block'); ?>
                </a>
                <a href="?page=we-custom-fields-block&tab=debug"
                    class="nav-tab <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('Debug Info', 'we-custom-fields-block'); ?>
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
        // Get all fields including excluded ones for admin display
        $custom_fields = $this->custom_fields->get_custom_fields(false);
    ?>
        <div class="tab-pane">
            <h2><?php echo esc_html__('Custom Fields Manager', 'we-custom-fields-block'); ?></h2>

            <div class="cfb-actions">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="scan_custom_fields">
                    <?php wp_nonce_field('cfb_scan_fields', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-primary">
                        🔍 <?php echo esc_html__('Scan Custom Fields', 'we-custom-fields-block'); ?>
                    </button>
                </form>

                <form method="post" style="display: inline; margin-left: 10px;">
                    <input type="hidden" name="action" value="clear_cache">
                    <?php wp_nonce_field('cfb_clear_cache', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-secondary">
                        🗑️ <?php echo esc_html__('Clear Cache', 'we-custom-fields-block'); ?>
                    </button>
                </form>
            </div>

            <div class="cfb-stats">
                <p><strong><?php echo esc_html(sprintf(__('Found %d custom fields', 'we-custom-fields-block'), count($custom_fields))); ?></strong></p>
            </div>

            <?php if (!empty($custom_fields)): ?>
                <form method="post" id="exclude-fields-form">
                    <input type="hidden" name="action" value="save_excluded_fields">
                    <?php wp_nonce_field('cfb_save_excluded_fields', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-primary" style="margin-bottom: 20px;">
                        💾 <?php echo esc_html__('Save Exclude Settings', 'we-custom-fields-block'); ?>
                    </button>
                    <p class="description" style="margin-top: 5px;">
                        <?php echo esc_html__('Changes are auto-saved when you check/uncheck fields.', 'we-custom-fields-block'); ?>
                        <strong><?php echo esc_html__('Reload the block editor page', 'we-custom-fields-block'); ?></strong>
                        <?php echo esc_html__('to see the changes in the block dropdown.', 'we-custom-fields-block'); ?>
                    </p>
                </form>

                <form method="post" id="delete-fields-form" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to delete the selected custom fields from ALL posts? This action cannot be undone!', 'we-custom-fields-block')); ?>');">
                    <input type="hidden" name="action" value="delete_custom_fields">
                    <?php wp_nonce_field('cfb_delete_fields', 'cfb_nonce'); ?>

                    <div style="margin: 20px 0;">
                        <button type="button" class="button" onclick="selectAllFields()"><?php echo esc_html__('Select All', 'we-custom-fields-block'); ?></button>
                        <button type="button" class="button" onclick="deselectAllFields()"><?php echo esc_html__('Deselect All', 'we-custom-fields-block'); ?></button>
                        <button type="submit" class="button button-danger" style="background: #dc3232; color: white; border-color: #dc3232;">
                            🗑️ <?php echo esc_html__('Delete Selected Fields', 'we-custom-fields-block'); ?>
                        </button>
                    </div>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 30px;"><input type="checkbox" id="select-all" onchange="toggleAllFields(this.checked)"></th>
                                <th><?php echo esc_html__('Field Key', 'we-custom-fields-block'); ?></th>
                                <th><?php echo esc_html__('Display Name', 'we-custom-fields-block'); ?></th>
                                <th><?php echo esc_html__('Sample Value', 'we-custom-fields-block'); ?></th>
                                <th><?php echo esc_html__('Posts Count', 'we-custom-fields-block'); ?></th>
                                <th style="width: 150px;"><?php echo esc_html__('Exclude from Block', 'we-custom-fields-block'); ?></th>
                                <th><?php echo esc_html__('Actions', 'we-custom-fields-block'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $excluded_fields = $this->custom_fields->get_excluded_fields();
                            foreach ($custom_fields as $field):
                                $posts_count = $this->custom_fields->count_posts_with_field($field['key']);
                                $is_excluded = in_array($field['key'], $excluded_fields);
                            ?>
                                <tr <?php echo $is_excluded ? 'style="opacity: 0.6; background-color: #f0f0f0;"' : ''; ?>>
                                    <td>
                                        <input type="checkbox" name="fields_to_delete[]" value="<?php echo esc_attr($field['key']); ?>" class="field-checkbox">
                                    </td>
                                    <td><code><?php echo esc_html($field['key']); ?></code></td>
                                    <td><?php echo esc_html($field['label']); ?></td>
                                    <td>
                                        <span class="cfb-sample-value">
                                            <?php echo esc_html(substr($field['value'], 0, 50)); ?>
                                            <?php if (strlen($field['value']) > 50): ?>...<?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo $posts_count; ?></strong> <?php echo esc_html(_n('post', 'posts', $posts_count, 'we-custom-fields-block')); ?>
                                    </td>
                                    <td>
                                        <label style="display: flex; align-items: center; gap: 5px;">
                                            <input type="checkbox"
                                                name="excluded_fields[]"
                                                value="<?php echo esc_attr($field['key']); ?>"
                                                form="exclude-fields-form"
                                                <?php checked($is_excluded); ?>
                                                onchange="updateRowStyle(this)">
                                            <span style="font-size: 12px; color: #666;">
                                                <?php echo $is_excluded ? esc_html__('Hidden', 'we-custom-fields-block') : esc_html__('Visible', 'we-custom-fields-block'); ?>
                                            </span>
                                        </label>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small" onclick="copyToClipboard('<?php echo esc_js($field['key']); ?>')">
                                            <?php echo esc_html__('Copy Key', 'we-custom-fields-block'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><?php echo esc_html__('No custom fields found. Click "Scan Custom Fields" to search for custom fields in your posts.', 'we-custom-fields-block'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <script>
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Field key copied to clipboard: ' + text);
                });
            }

            function toggleAllFields(checked) {
                var checkboxes = document.querySelectorAll('.field-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = checked;
                });
            }

            function selectAllFields() {
                document.getElementById('select-all').checked = true;
                toggleAllFields(true);
            }

            function deselectAllFields() {
                document.getElementById('select-all').checked = false;
                toggleAllFields(false);
            }

            function updateRowStyle(checkbox) {
                var row = checkbox.closest('tr');
                var label = checkbox.nextElementSibling;
                if (checkbox.checked) {
                    row.style.opacity = '0.6';
                    row.style.backgroundColor = '#f0f0f0';
                    label.textContent = 'Hidden';
                } else {
                    row.style.opacity = '1';
                    row.style.backgroundColor = '';
                    label.textContent = 'Visible';
                }
            }

            // Auto-save excluded fields when checkbox changes
            function saveExcludedFields() {
                var form = document.getElementById('exclude-fields-form');
                var formData = new FormData(form);
                formData.append('action', 'cfb_save_excluded_fields_ajax');
                formData.append('cfb_nonce', '<?php echo wp_create_nonce('cfb_save_excluded_fields_ajax'); ?>');

                fetch(ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            // Show success message
                            var existingNotice = document.querySelector('.cfb-auto-save-notice');
                            if (existingNotice) {
                                existingNotice.remove();
                            }

                            var notice = document.createElement('div');
                            notice.className = 'notice notice-success is-dismissible cfb-auto-save-notice';
                            notice.style.marginTop = '10px';
                            notice.style.marginBottom = '10px';
                            notice.innerHTML = '<p>' + data.data.message + ' <strong>Please reload the block editor page to see changes.</strong></p>';
                            var form = document.getElementById('exclude-fields-form');
                            form.parentNode.insertBefore(notice, form.nextSibling);

                            // Auto-dismiss after 5 seconds
                            setTimeout(function() {
                                if (notice.parentNode) {
                                    notice.remove();
                                }
                            }, 5000);
                        }
                    })
                    .catch(function(error) {
                        console.error('Error saving excluded fields:', error);
                    });
            }

            // Add event listeners to all exclude checkboxes
            document.addEventListener('DOMContentLoaded', function() {
                var excludeCheckboxes = document.querySelectorAll('input[name="excluded_fields[]"]');
                excludeCheckboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        updateRowStyle(this);
                        // Debounce: save after 500ms of no changes
                        clearTimeout(window.excludeSaveTimeout);
                        window.excludeSaveTimeout = setTimeout(saveExcludedFields, 500);
                    });
                });
            });
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
            <h2><?php echo esc_html__('Settings', 'we-custom-fields-block'); ?></h2>

            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <?php wp_nonce_field('cfb_save_settings', 'cfb_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('GitHub Token', 'we-custom-fields-block'); ?></th>
                        <td>
                            <input type="password" name="cfb_github_token"
                                value="<?php echo esc_attr(get_option('cfb_github_token')); ?>"
                                class="regular-text" />
                            <p class="description"><?php echo esc_html__('Personal Access Token for automatic plugin updates', 'we-custom-fields-block'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings', 'we-custom-fields-block')); ?>
            </form>
        </div>
    <?php
    }

    /**
     * Debug tab
     */
    private function debug_tab()
    {
        $latest_release = $this->updates->get_latest_release();
        $custom_fields = $this->custom_fields->get_custom_fields();
        $github_token = get_option('cfb_github_token');
    ?>
        <div class="tab-pane">
            <h2><?php echo esc_html__('Debug Information', 'we-custom-fields-block'); ?></h2>

            <h3><?php echo esc_html__('Update System', 'we-custom-fields-block'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__('Current Version', 'we-custom-fields-block'); ?></th>
                    <td><strong><?php echo CFB_VERSION; ?></strong></td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Latest Version', 'we-custom-fields-block'); ?></th>
                    <td>
                        <?php if ($latest_release): ?>
                            <strong><?php echo esc_html($latest_release['version']); ?></strong>
                            <?php if (version_compare($latest_release['version'], CFB_VERSION, '>')): ?>
                                <span style="color: green;">✅ <?php echo esc_html__('Update available!', 'we-custom-fields-block'); ?></span>
                            <?php else: ?>
                                <span style="color: blue;">✅ <?php echo esc_html__('Up to date', 'we-custom-fields-block'); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: red;">❌ <?php echo esc_html__('Could not fetch latest release', 'we-custom-fields-block'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('GitHub Token', 'we-custom-fields-block'); ?></th>
                    <td>
                        <?php if ($github_token): ?>
                            <span style="color: green;">✅ <?php echo esc_html__('Set', 'we-custom-fields-block'); ?> (<?php echo substr($github_token, 0, 8) . '...'; ?>)</span>
                        <?php else: ?>
                            <span style="color: red;">❌ <?php echo esc_html__('Not set', 'we-custom-fields-block'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('GitHub API Test', 'we-custom-fields-block'); ?></th>
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
                                echo '<span style="color: blue;">' . esc_html__('Response Code:', 'we-custom-fields-block') . ' ' . esc_html($response_code) . '</span>';

                                if ($response_code === 200) {
                                    $body = wp_remote_retrieve_body($response);
                                    $release = json_decode($body, true);

                                    if ($release && isset($release['tag_name'])) {
                                        echo '<br><strong>' . esc_html__('Latest Release:', 'we-custom-fields-block') . '</strong> ' . esc_html($release['tag_name']);

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
                                        echo '<br><strong>' . esc_html__('ZIP Asset:', 'we-custom-fields-block') . '</strong> ' . ($has_zip ? '✅ ' . esc_html__('Present', 'we-custom-fields-block') : '❌ ' . esc_html__('Missing', 'we-custom-fields-block'));
                                    } else {
                                        echo '<br><span style="color: red;">❌ ' . esc_html__('Could not parse release data', 'we-custom-fields-block') . '</span>';
                                    }
                                } else {
                                    echo '<br><span style="color: red;">❌ ' . esc_html__('API Error:', 'we-custom-fields-block') . ' ' . esc_html($response_code) . '</span>';
                                }
                            }
                        } catch (Exception $e) {
                            echo '<span style="color: red;">❌ Exception: ' . esc_html($e->getMessage()) . '</span>';
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <h3><?php echo esc_html__('Custom Fields Cache', 'we-custom-fields-block'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__('Cached Fields', 'we-custom-fields-block'); ?></th>
                    <td><?php echo count($custom_fields); ?> <?php echo esc_html__('fields', 'we-custom-fields-block'); ?></td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Cache Status', 'we-custom-fields-block'); ?></th>
                    <td>
                        <?php
                        $cache_data = get_transient('cfb_all_custom_fields');
                        if ($cache_data): ?>
                            <span style="color: green;">✅ <?php echo esc_html__('Active', 'we-custom-fields-block'); ?> (<?php echo count($cache_data); ?> <?php echo esc_html__('fields', 'we-custom-fields-block'); ?>)</span>
                        <?php else: ?>
                            <span style="color: red;">❌ <?php echo esc_html__('Empty', 'we-custom-fields-block'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Update Cache', 'we-custom-fields-block'); ?></th>
                    <td>
                        <?php
                        $update_cache = get_transient('cfb_latest_release');
                        if ($update_cache): ?>
                            <span style="color: green;">✅ <?php echo esc_html__('Active', 'we-custom-fields-block'); ?></span>
                        <?php else: ?>
                            <span style="color: red;">❌ <?php echo esc_html__('Empty', 'we-custom-fields-block'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h3><?php echo esc_html__('System Information', 'we-custom-fields-block'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__('WordPress Version', 'we-custom-fields-block'); ?></th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('PHP Version', 'we-custom-fields-block'); ?></th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Plugin Directory', 'we-custom-fields-block'); ?></th>
                    <td><code><?php echo CFB_PLUGIN_DIR; ?></code></td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Plugin URL', 'we-custom-fields-block'); ?></th>
                    <td><code><?php echo CFB_PLUGIN_URL; ?></code></td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('GitHub Repository', 'we-custom-fields-block'); ?></th>
                    <td><code><?php echo CFB_GITHUB_REPO; ?></code></td>
                </tr>
            </table>

            <h3><?php echo esc_html__('Cache Management', 'we-custom-fields-block'); ?></h3>
            <div class="cfb-actions">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="clear_update_cache">
                    <?php wp_nonce_field('cfb_clear_update_cache', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-secondary">
                        🗑️ <?php echo esc_html__('Clear Update Cache', 'we-custom-fields-block'); ?>
                    </button>
                </form>

                <form method="post" style="display: inline; margin-left: 10px;">
                    <input type="hidden" name="action" value="clear_cache">
                    <?php wp_nonce_field('cfb_clear_cache', 'cfb_nonce'); ?>
                    <button type="submit" class="button button-secondary">
                        🗑️ <?php echo esc_html__('Clear Custom Fields Cache', 'we-custom-fields-block'); ?>
                    </button>
                </form>

                <a href="<?php echo admin_url('update-core.php'); ?>" class="button button-primary" style="margin-left: 10px;">
                    🔄 <?php echo esc_html__('Check for Updates', 'we-custom-fields-block'); ?>
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
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'we-custom-fields-block') . '</p></div>';
        });
    }

    /**
     * Save excluded fields
     */
    private function save_excluded_fields()
    {
        if (!wp_verify_nonce($_POST['cfb_nonce'], 'cfb_save_excluded_fields')) {
            wp_die('Security check failed');
        }

        $excluded_fields = array();
        if (isset($_POST['excluded_fields']) && is_array($_POST['excluded_fields'])) {
            $excluded_fields = array_map('sanitize_text_field', $_POST['excluded_fields']);
        }

        update_option('cfb_excluded_fields', $excluded_fields);

        // Clear cache to refresh block dropdown
        $this->custom_fields->clear_custom_fields_cache();

        add_action('admin_notices', function () use ($excluded_fields) {
            $count = count($excluded_fields);
            echo '<div class="notice notice-success"><p>';
            echo sprintf(
                __('Exclude settings saved! %d field(s) will be hidden from the block dropdown.', 'we-custom-fields-block'),
                $count
            );
            echo '</p></div>';
        });
    }

    /**
     * AJAX handler for saving excluded fields
     */
    public function ajax_save_excluded_fields()
    {
        check_ajax_referer('cfb_save_excluded_fields_ajax', 'cfb_nonce');

        $excluded_fields = array();
        if (isset($_POST['excluded_fields']) && is_array($_POST['excluded_fields'])) {
            $excluded_fields = array_map('sanitize_text_field', $_POST['excluded_fields']);
        }

        update_option('cfb_excluded_fields', $excluded_fields);

        // Clear cache to refresh block dropdown
        $this->custom_fields->clear_custom_fields_cache();

        $count = count($excluded_fields);
        wp_send_json_success(array(
            'message' => sprintf(
                __('Exclude settings saved! %d field(s) will be hidden from the block dropdown.', 'we-custom-fields-block'),
                $count
            )
        ));
    }
}
