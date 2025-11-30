<?php

/**
 * Advanced Custom Fields (ACF) Handler
 *
 * @package WE_Custom_Fields_Block
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFB_ACF_Fields
{
    /**
     * Check if ACF is active
     */
    public function is_acf_active()
    {
        return function_exists('acf_get_field_groups');
    }

    /**
     * Get all ACF fields
     * @param bool $exclude_hidden Whether to exclude fields marked as hidden in admin
     */
    public function get_acf_fields($exclude_hidden = true)
    {
        if (!$this->is_acf_active()) {
            return array();
        }

        // Try to get cached ACF fields first
        $cached_fields = get_transient('cfb_all_acf_fields');

        if ($cached_fields !== false && !empty($cached_fields)) {
            $cached_fields = array_values($cached_fields);
        } else {
            // Build cache
            $cached_fields = $this->build_acf_fields_cache();
            set_transient('cfb_all_acf_fields', $cached_fields, 3600);
        }

        // Filter out excluded fields if requested (for block dropdown)
        if ($exclude_hidden) {
            $excluded_fields = get_option('cfb_excluded_acf_fields', array());
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
     * Build cache of all ACF fields
     */
    private function build_acf_fields_cache()
    {
        if (!$this->is_acf_active()) {
            return array();
        }

        $fields = array();
        $field_groups = acf_get_field_groups();

        foreach ($field_groups as $field_group) {
            $acf_fields = acf_get_fields($field_group);

            if (!$acf_fields) {
                continue;
            }

            foreach ($acf_fields as $acf_field) {
                // Get sample value from database
                $sample_value = $this->get_sample_value($acf_field['key']);

                $fields[] = array(
                    'key' => $acf_field['key'],
                    'name' => $acf_field['name'],
                    'label' => $acf_field['label'],
                    'type' => isset($acf_field['type']) ? $acf_field['type'] : 'text',
                    'field_group' => $field_group['title'],
                    'field_group_key' => $field_group['key'],
                    'value' => $sample_value,
                );
            }
        }

        return $fields;
    }

    /**
     * Get sample value for an ACF field
     */
    private function get_sample_value($field_key)
    {
        global $wpdb;

        // ACF stores values with field_ prefix in meta_key
        // But also stores raw values with the field name
        $meta_key = $field_key;

        // Try to get a sample value
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

        // If not found, try with field name (ACF sometimes stores both)
        if (!$sample_value) {
            // Get field name from key
            $field = acf_get_field($field_key);
            if ($field && isset($field['name'])) {
                $sample_value = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_value 
                        FROM {$wpdb->postmeta} pm
                        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                        WHERE pm.meta_key = %s 
                        AND p.post_status = 'publish'
                        ORDER BY p.post_date DESC
                        LIMIT 1",
                        $field['name']
                    )
                );
            }
        }

        return $sample_value ? $sample_value : '';
    }

    /**
     * Get ACF field groups
     */
    public function get_acf_field_groups()
    {
        if (!$this->is_acf_active()) {
            return array();
        }

        return acf_get_field_groups();
    }

    /**
     * Count posts with a specific ACF field
     */
    public function count_posts_with_field($field_key)
    {
        global $wpdb;

        // Count posts that have this ACF field
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = %s",
                $field_key
            )
        );

        // Also check for field name if available
        $field = acf_get_field($field_key);
        if ($field && isset($field['name'])) {
            $count_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_id) 
                    FROM {$wpdb->postmeta} 
                    WHERE meta_key = %s",
                    $field['name']
                )
            );
            // Use the higher count
            $count = max((int) $count, (int) $count_name);
        }

        return (int) $count;
    }

    /**
     * Delete ACF field values from all posts
     * Note: This only deletes VALUES, not the field definitions
     */
    public function delete_acf_field_values($field_keys)
    {
        if (!is_array($field_keys)) {
            $field_keys = array($field_keys);
        }

        global $wpdb;
        $deleted_count = 0;
        $posts_affected = 0;

        foreach ($field_keys as $field_key) {
            // Get field to find field name
            $field = acf_get_field($field_key);
            $field_name = $field && isset($field['name']) ? $field['name'] : null;

            // Count posts before deletion
            $posts_before = $this->count_posts_with_field($field_key);

            // Delete by field key (ACF's internal key)
            $result1 = $wpdb->delete(
                $wpdb->postmeta,
                array('meta_key' => $field_key),
                array('%s')
            );

            // Also delete by field name if available
            $result2 = false;
            if ($field_name) {
                $result2 = $wpdb->delete(
                    $wpdb->postmeta,
                    array('meta_key' => $field_name),
                    array('%s')
                );
            }

            if ($result1 !== false || $result2 !== false) {
                $deleted_count++;
                $posts_affected += $posts_before;
            }
        }

        // Clear cache
        $this->clear_acf_fields_cache();

        return array(
            'deleted_count' => $deleted_count,
            'posts_affected' => $posts_affected
        );
    }

    /**
     * Clear ACF fields cache
     */
    public function clear_acf_fields_cache()
    {
        delete_transient('cfb_all_acf_fields');
    }

    /**
     * Get excluded ACF fields option
     */
    public function get_excluded_fields()
    {
        return get_option('cfb_excluded_acf_fields', array());
    }

    /**
     * Check if an ACF field is excluded
     */
    public function is_field_excluded($field_key)
    {
        $excluded_fields = $this->get_excluded_fields();
        return in_array($field_key, $excluded_fields);
    }

    /**
     * Format field name for display
     */
    public function format_field_name($key)
    {
        // Try to get ACF field for proper label
        if ($this->is_acf_active()) {
            $field = acf_get_field($key);
            if ($field && isset($field['label'])) {
                return $field['label'];
            }
        }

        // Fallback to formatting the key
        return ucwords(str_replace(array('_', '-'), ' ', $key));
    }

    /**
     * Check if a meta key belongs to an ACF field
     * @param string $meta_key The meta key to check
     * @return bool True if the key belongs to an ACF field
     */
    public function is_acf_field_key($meta_key)
    {
        if (!$this->is_acf_active()) {
            return false;
        }

        // ACF field keys start with "field_"
        if (strpos($meta_key, 'field_') === 0) {
            // Check if it's a valid ACF field
            $field = acf_get_field($meta_key);
            if ($field) {
                return true;
            }
        }

        // Also check if the meta_key matches an ACF field name
        $field_groups = acf_get_field_groups();
        foreach ($field_groups as $field_group) {
            $acf_fields = acf_get_fields($field_group);
            if ($acf_fields) {
                foreach ($acf_fields as $acf_field) {
                    // Check both field key and field name
                    if ($acf_field['key'] === $meta_key || (isset($acf_field['name']) && $acf_field['name'] === $meta_key)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get all ACF field keys and names (for filtering)
     * @return array Array of ACF field keys and names
     */
    public function get_all_acf_keys()
    {
        if (!$this->is_acf_active()) {
            return array();
        }

        $keys = array();
        $field_groups = acf_get_field_groups();

        foreach ($field_groups as $field_group) {
            $acf_fields = acf_get_fields($field_group);
            if ($acf_fields) {
                foreach ($acf_fields as $acf_field) {
                    $keys[] = $acf_field['key'];
                    if (isset($acf_field['name'])) {
                        $keys[] = $acf_field['name'];
                    }
                }
            }
        }

        return array_unique($keys);
    }
}
