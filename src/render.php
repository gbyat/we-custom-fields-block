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

// Get block attributes
$field_key = isset($attributes['fieldKey']) ? $attributes['fieldKey'] : '';
$display_type = isset($attributes['displayType']) ? $attributes['displayType'] : 'paragraph';
$heading_level = isset($attributes['headingLevel']) ? intval($attributes['headingLevel']) : 2;

// Only render if we have a field key and a value
if (!empty($field_key)) {
    // Get field value
    $field_value = get_post_meta(get_the_ID(), $field_key, true);

    // Only render if we have a value (not empty, not false)
    if ($field_value !== false && $field_value !== '') {
        // Convert value to string for display
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
                    <?php echo esc_html($display_value); ?>
                </<?php echo esc_attr($tag); ?>>
            <?php
                break;

            case 'div':
            ?>
                <div <?php echo $wrapper_attributes; ?>>
                    <?php echo esc_html($display_value); ?>
                </div>
            <?php
                break;

            case 'paragraph':
            default:
            ?>
                <p <?php echo $wrapper_attributes; ?>>
                    <?php echo esc_html($display_value); ?>
                </p>
<?php
                break;
        }
    }
}
