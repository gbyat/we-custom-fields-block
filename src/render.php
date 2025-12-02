<?php

/**
 * Block render template
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get block attributes.
$field_key = isset($attributes['fieldKey']) ? $attributes['fieldKey'] : '';
$display_type = isset($attributes['displayType']) ? $attributes['displayType'] : 'paragraph';
$heading_level = isset($attributes['headingLevel']) ? intval($attributes['headingLevel']) : 2;

// Only render if we have a field key and a value.
if (!empty($field_key)) {
    $post_id = get_the_ID();
    $field_value = '';
    $is_wysiwyg = false;

    /**
     * Try to resolve ACF fields first (by key or name), then fall back to native meta.
     *
     * ACF usually stores:
     * - value under meta_key = field name (e.g. "my_field")
     * - internal reference under meta_key = "_my_field" with value "field_abc..."
     */
    if (function_exists('acf_get_field')) {
        // Check if this looks like an ACF field key and try to resolve its name.
        $acf_field = acf_get_field($field_key);

        if ($acf_field && isset($acf_field['name']) && $acf_field['name']) {
            // Remember if this is a WYSIWYG field so we can allow HTML output.
            if (isset($acf_field['type']) && 'wysiwyg' === $acf_field['type']) {
                $is_wysiwyg = true;
            }

            // First try via raw meta by field name.
            $field_value = get_post_meta($post_id, $acf_field['name'], true);

            // If empty, try ACF API (handles complex/serialized types).
            if ($field_value === '' || $field_value === false) {
                if (function_exists('get_field')) {
                    $field_value = get_field($acf_field['name'], $post_id);
                }
            }
        } else {
            // Maybe the stored key IS the ACF field name.
            $field_value = get_post_meta($post_id, $field_key, true);

            if ($field_value === '' || $field_value === false) {
                if (function_exists('get_field')) {
                    $field_value = get_field($field_key, $post_id);
                }
            }
        }
    } else {
        // No ACF available – treat as normal custom field.
        $field_value = get_post_meta($post_id, $field_key, true);
    }

    // Only render if we have a value (not empty, not false, not null).
    if ($field_value !== false && $field_value !== '' && $field_value !== null) {
        // Convert value to string for display.
        $display_value = (string) $field_value;

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
?>
                <<?php echo esc_attr($tag); ?> <?php echo $wrapper_attributes; ?>>
                    <?php
                    if ($is_wysiwyg) {
                        echo wp_kses_post($display_value);
                    } else {
                        echo esc_html($display_value);
                    }
                    ?>
                </<?php echo esc_attr($tag); ?>>
            <?php
                break;

            case 'div':
            ?>
                <div <?php echo $wrapper_attributes; ?>>
                    <?php
                    if ($is_wysiwyg) {
                        echo wp_kses_post($display_value);
                    } else {
                        echo esc_html($display_value);
                    }
                    ?>
                </div>
            <?php
                break;

            case 'paragraph':
            default:
            ?>
                <p <?php echo $wrapper_attributes; ?>>
                    <?php
                    if ($is_wysiwyg) {
                        echo wp_kses_post($display_value);
                    } else {
                        echo esc_html($display_value);
                    }
                    ?>
                </p>
<?php
                break;
        }
    }
}
