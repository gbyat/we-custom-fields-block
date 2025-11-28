<?php

/**
 * Plugin Name: WE Custom Fields Block
 * Plugin URI: https://github.com/gbyat/we-custom-fields-block
 * Description: Fügt native WordPress Custom Fields als Blöcke mit Typografie- und Farboptionen ein
 * Version: 0.1.15
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
define('CFB_VERSION', '0.1.15');
define('CFB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFB_GITHUB_REPO', 'gbyat/we-custom-fields-block');

// Include required files
require_once CFB_PLUGIN_DIR . 'inc/class-assets.php';
require_once CFB_PLUGIN_DIR . 'inc/class-updates.php';
require_once CFB_PLUGIN_DIR . 'inc/class-custom-fields.php';
require_once CFB_PLUGIN_DIR . 'inc/class-admin.php';
require_once CFB_PLUGIN_DIR . 'inc/class-custom-fields-block.php';

// Initialize the plugin
$custom_fields_block = new CustomFieldsBlock();
$custom_fields_block->__init__();

// Register block
add_action('init', function () {
    register_block_type(CFB_PLUGIN_DIR . 'build');
});
