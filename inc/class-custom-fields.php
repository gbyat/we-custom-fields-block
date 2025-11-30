<?php

/**
 * Custom Fields Handler
 *
 * @package WE_Custom_Fields_Block
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFB_Custom_Fields
{
    private $acf_fields = null;

    /**
     * Set ACF fields handler (for filtering ACF fields from native list)
     */
    public function set_acf_fields_handler($acf_fields)
    {
        $this->acf_fields = $acf_fields;
    }

    /**
     * Get all custom fields for the current post
     * @param bool $exclude_hidden Whether to exclude fields marked as hidden in admin
     */
    public function get_custom_fields($exclude_hidden = true)
    {
        // Try to get cached custom fields first
        $cached_fields = get_transient('cfb_all_custom_fields');

        if ($cached_fields !== false && !empty($cached_fields)) {
            // Filter out fields starting with _ from cache as well
            $cached_fields = array_filter($cached_fields, function ($field) {
                return strpos($field['key'], '_') !== 0;
            });
            $cached_fields = array_values($cached_fields);
        } else {
            // If no cache or empty cache, build it
            $cached_fields = $this->build_custom_fields_cache();

            // Filter out any fields starting with _ (additional safety)
            $cached_fields = array_filter($cached_fields, function ($field) {
                return strpos($field['key'], '_') !== 0;
            });
            $cached_fields = array_values($cached_fields);

            // Cache for 1 hour
            set_transient('cfb_all_custom_fields', $cached_fields, 3600);

            // If still no fields, try a simple fallback
            if (empty($cached_fields)) {
                $cached_fields = $this->get_fallback_custom_fields();
                // Filter fallback results as well
                $cached_fields = array_filter($cached_fields, function ($field) {
                    return strpos($field['key'], '_') !== 0;
                });
                $cached_fields = array_values($cached_fields);
            }
        }

        // Filter out excluded fields if requested (for block dropdown)
        if ($exclude_hidden) {
            $excluded_fields = get_option('cfb_excluded_fields', array());
            if (!empty($excluded_fields) && is_array($excluded_fields)) {
                $cached_fields = array_filter($cached_fields, function ($field) use ($excluded_fields) {
                    return !in_array($field['key'], $excluded_fields);
                });
                $cached_fields = array_values($cached_fields);
            }
        }

        return $cached_fields;
    }

    /**
     * Build cache of all custom fields from the site
     */
    private function build_custom_fields_cache()
    {
        global $wpdb;

        $fields = array();

        // Get all custom field keys from the database - exclude fields starting with _
        // First, get ALL meta keys to see what we have
        $all_meta_keys = $wpdb->get_col(
            "SELECT DISTINCT meta_key 
            FROM {$wpdb->postmeta} 
            ORDER BY meta_key"
        );

        // Filter out fields starting with underscore in PHP (more reliable)
        $meta_keys = array_filter($all_meta_keys, function ($key) {
            return strpos($key, '_') !== 0;
        });

        // Filter out ACF fields if ACF is active
        if ($this->acf_fields && method_exists($this->acf_fields, 'is_acf_active') && $this->acf_fields->is_acf_active()) {
            $acf_keys = $this->acf_fields->get_all_acf_keys();
            if (!empty($acf_keys)) {
                $meta_keys = array_filter($meta_keys, function ($key) use ($acf_keys) {
                    return !in_array($key, $acf_keys);
                });
            }
        }

        // Re-index array
        $meta_keys = array_values($meta_keys);

        // Debug: Log what we found
        error_log('Custom Fields Block Debug: Found ' . count($meta_keys) . ' meta keys');
        if (count($meta_keys) > 0 && count($meta_keys) <= 50) {
            error_log('Custom Fields Block Debug: Meta keys: ' . print_r($meta_keys, true));
        } else if (count($meta_keys) > 50) {
            error_log('Custom Fields Block Debug: First 50 meta keys: ' . print_r(array_slice($meta_keys, 0, 50), true));
        }

        if (empty($meta_keys)) {
            // Try even less restrictive query, but still exclude fields starting with _
            $meta_keys = $wpdb->get_col(
                "SELECT DISTINCT meta_key 
                FROM {$wpdb->postmeta} 
                WHERE meta_key NOT LIKE '_%'
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
            // Skip fields starting with underscore (additional safety check)
            if (strpos($meta_key, '_') === 0) {
                continue;
            }

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

            // Add field even if no sample value (show all fields, not just those with values)
            $fields[] = array(
                'key' => $meta_key,
                'label' => $this->format_field_name($meta_key),
                'value' => $sample_value ? $sample_value : '',
            );
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

                // Skip ACF fields if ACF is active
                if ($this->acf_fields && method_exists($this->acf_fields, 'is_acf_field_key') && $this->acf_fields->is_acf_field_key($key)) {
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
                // Skip ACF fields if ACF is active
                if ($this->acf_fields && method_exists($this->acf_fields, 'is_acf_field_key') && $this->acf_fields->is_acf_field_key($meta_key)) {
                    continue;
                }

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
    public function format_field_name($key)
    {
        return ucwords(str_replace(array('_', '-'), ' ', $key));
    }

    /**
     * Count posts with a specific custom field
     */
    public function count_posts_with_field($meta_key)
    {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = %s",
                $meta_key
            )
        );

        return (int) $count;
    }

    /**
     * Delete custom fields from all posts
     */
    public function delete_custom_fields()
    {
        if (!wp_verify_nonce($_POST['cfb_nonce'], 'cfb_delete_fields')) {
            wp_die('Security check failed');
        }

        if (!isset($_POST['fields_to_delete']) || !is_array($_POST['fields_to_delete'])) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . esc_html__('No fields selected for deletion.', 'we-custom-fields-block') . '</p></div>';
            });
            return;
        }

        global $wpdb;

        $fields_to_delete = array_map('sanitize_text_field', $_POST['fields_to_delete']);
        $deleted_count = 0;
        $posts_affected = 0;

        foreach ($fields_to_delete as $meta_key) {
            // Count posts before deletion
            $posts_before = $this->count_posts_with_field($meta_key);

            // Delete all meta entries for this key
            $result = $wpdb->delete(
                $wpdb->postmeta,
                array('meta_key' => $meta_key),
                array('%s')
            );

            if ($result !== false) {
                $deleted_count++;
                $posts_affected += $posts_before;
            }
        }

        // Clear cache
        $this->clear_custom_fields_cache();

        add_action('admin_notices', function () use ($deleted_count, $posts_affected) {
            echo '<div class="notice notice-success"><p>';
            echo sprintf(
                __('Successfully deleted %d custom field(s) from %d post(s).', 'we-custom-fields-block'),
                $deleted_count,
                $posts_affected
            );
            echo '</p></div>';
        });
    }

    /**
     * Scan custom fields
     */
    public function scan_custom_fields()
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
            echo '<div class="notice notice-success"><p>' . sprintf(__('Found %d custom fields!', 'we-custom-fields-block'), count($fields)) . '</p></div>';
        });
    }

    /**
     * Get excluded fields option
     */
    public function get_excluded_fields()
    {
        return get_option('cfb_excluded_fields', array());
    }

    /**
     * Check if a field is excluded
     */
    public function is_field_excluded($field_key)
    {
        $excluded_fields = $this->get_excluded_fields();
        return in_array($field_key, $excluded_fields);
    }
}
