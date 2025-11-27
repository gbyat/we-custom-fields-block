<?php

/**
 * Main Plugin Class
 *
 * @package WE_Custom_Fields_Block
 */

if (!defined('ABSPATH')) {
    exit;
}

class CustomFieldsBlock
{
    private $assets;
    private $updates;
    private $custom_fields;
    private $admin;

    public function __construct()
    {
        // Initialize sub-classes
        $this->assets = new CFB_Assets();
        $this->updates = new CFB_Updates();
        $this->custom_fields = new CFB_Custom_Fields();
        $this->admin = new CFB_Admin($this->custom_fields, $this->updates);
    }

    public function __init__()
    {
        // Assets and translations
        add_action('plugins_loaded', array($this->assets, 'load_textdomain'));
        add_action('init', array($this->assets, 'set_block_script_translations'), 20);
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));

        // Update system
        add_filter('pre_set_site_transient_update_plugins', array($this->updates, 'check_for_updates'));
        add_filter('plugins_api', array($this->updates, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this->updates, 'upgrader_post_install'), 10, 3);

        // Admin settings
        add_action('admin_menu', array($this->admin, 'remove_old_menu'), 5);
        add_action('admin_menu', array($this->admin, 'add_admin_menu'), 10);
        add_action('admin_init', array($this->admin, 'init_settings'));

        // Cache management
        add_action('save_post', array($this->custom_fields, 'clear_custom_fields_cache'));
        add_action('deleted_post', array($this->custom_fields, 'clear_custom_fields_cache'));
        add_action('updated_post_meta', array($this->custom_fields, 'clear_custom_fields_cache'));
        add_action('added_post_meta', array($this->custom_fields, 'clear_custom_fields_cache'));
        add_action('deleted_post_meta', array($this->custom_fields, 'clear_custom_fields_cache'));
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets()
    {
        $custom_fields = $this->custom_fields->get_custom_fields();
        $this->assets->enqueue_block_editor_assets($custom_fields);
    }
}
