<?php
/**
 * Plugin Name: CEP Affiliate Hosting
 * Description: Manage hosting affiliate links with short URLs and track clicks.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CEP_AFFILIATE_HOSTING_PATH', plugin_dir_path(__FILE__));
define('CEP_AFFILIATE_HOSTING_URL', plugin_dir_url(__FILE__));

// Include required files
require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/database.php';
require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/redirect.php';
require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/tracker.php';
require_once CEP_AFFILIATE_HOSTING_PATH . 'admin/settings.php';

// Activation hook: Create database tables
register_activation_hook(__FILE__, 'cep_affiliate_hosting_create_tables');

// Add admin menu
add_action('admin_menu', 'cep_affiliate_hosting_add_admin_menu');

// Handle short URL redirection
add_action('init', 'cep_affiliate_hosting_handle_redirect');
