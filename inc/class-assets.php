<?php

/**
 * Assets and Translations Handler
 *
 * @package WE_Custom_Fields_Block
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFB_Assets
{
    /**
     * Load plugin translations
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'we-custom-fields-block',
            false,
            dirname(plugin_basename(CFB_PLUGIN_DIR . '/we-custom-fields-block.php')) . '/languages'
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

        $languages_path = CFB_PLUGIN_DIR . 'languages';

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
     * Enqueue block editor assets
     * Only for passing custom fields data to the editor
     */
    public function enqueue_block_editor_assets($custom_fields)
    {
        // The script handle is generated from block name in block.json
        // Try different possible handles
        $possible_handles = array(
            'we-custom-fields-block-custom-field-editor-script',
            'we-custom-fields-block-custom-field-script',
            'we-custom-fields-block-editor',
        );

        // Get excluded fields hash for cache busting
        // This ensures the block editor gets fresh data when excluded fields change
        $excluded_fields = get_option('cfb_excluded_fields', array());
        $cache_key = md5(serialize($excluded_fields));

        foreach ($possible_handles as $handle) {
            if (wp_script_is($handle, 'registered')) {
                wp_localize_script($handle, 'cfbData', array(
                    'customFields' => $custom_fields,
                    'nonce' => wp_create_nonce('cfb_nonce'),
                    'cacheKey' => $cache_key, // For cache busting
                ));
                break;
            }
        }
    }
}
