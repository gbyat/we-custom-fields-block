<?php

/**
 * Block render callback
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block HTML.
 */
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
