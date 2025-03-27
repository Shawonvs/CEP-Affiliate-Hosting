<?php
/**
 * Plugin Name: CEP Affiliate Hosting
 * Description: Manage hosting affiliate links with short URLs and track clicks.
 * Version: 1.1
 * Author: Shawon
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CEP_AFFILIATE_HOSTING_PATH', plugin_dir_path(__FILE__));
define('CEP_AFFILIATE_HOSTING_URL', plugin_dir_url(__FILE__));

// Include required files with error handling
if (file_exists(CEP_AFFILIATE_HOSTING_PATH . 'includes/database.php')) {
    require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/database.php';
} else {
    error_log('Missing file: includes/database.php');
}

if (file_exists(CEP_AFFILIATE_HOSTING_PATH . 'includes/redirect.php')) {
    require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/redirect.php';
} else {
    error_log('Missing file: includes/redirect.php');
}

if (file_exists(CEP_AFFILIATE_HOSTING_PATH . 'includes/tracker.php')) {
    require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/tracker.php';
} else {
    error_log('Missing file: includes/tracker.php');
}

if (file_exists(CEP_AFFILIATE_HOSTING_PATH . 'includes/api-fetcher.php')) {
    require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/api-fetcher.php';
} else {
    error_log('Missing file: includes/api-fetcher.php');
}

if (file_exists(CEP_AFFILIATE_HOSTING_PATH . 'includes/content-generator.php')) {
    require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/content-generator.php';
} else {
    error_log('Missing file: includes/content-generator.php');
}

if (file_exists(CEP_AFFILIATE_HOSTING_PATH . 'admin/settings.php')) {
    require_once CEP_AFFILIATE_HOSTING_PATH . 'admin/settings.php';
} else {
    error_log('Missing file: admin/settings.php');
}

// Activation hook: Create database tables with error handling
register_activation_hook(__FILE__, function () {
    try {
        if (!function_exists('cep_affiliate_hosting_create_tables')) {
            error_log('Critical error: Function cep_affiliate_hosting_create_tables is not defined.');
            return;
        }
        cep_affiliate_hosting_create_tables();
    } catch (Exception $e) {
        error_log('Activation error: ' . $e->getMessage());
    }
});

// Add admin menu
add_action('admin_menu', function () {
    try {
        if (!function_exists('cep_affiliate_hosting_add_admin_menu')) {
            error_log('Critical error: Function cep_affiliate_hosting_add_admin_menu is not defined.');
            return;
        }
        cep_affiliate_hosting_add_admin_menu();
    } catch (Exception $e) {
        error_log('Admin menu error: ' . $e->getMessage());
    }
});

// Enqueue admin styles for responsiveness
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_cep-affiliate-hosting') {
        wp_enqueue_style('cep-affiliate-hosting-admin', CEP_AFFILIATE_HOSTING_URL . 'assets/css/admin-responsive.css', [], '1.0.0');
    }
});

// Handle short URL redirection
add_action('init', function () {
    try {
        if (!function_exists('cep_affiliate_hosting_handle_redirect')) {
            error_log('Critical error: Function cep_affiliate_hosting_handle_redirect is not defined.');
            return;
        }

        if (isset($_GET['cep_redirect'])) {
            global $wpdb;

            $slug = sanitize_title($_GET['cep_redirect']);
            $links_table = $wpdb->prefix . 'cep_affiliate_links';
            $clicks_table = $wpdb->prefix . 'cep_affiliate_clicks';

            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $links_table WHERE slug = %s", $slug));
            if ($link) {
                // Increment click count
                $wpdb->update($links_table, ['click_count' => $link->click_count + 1], ['id' => $link->id]);

                // Log click details
                $wpdb->insert($clicks_table, [
                    'link_id' => $link->id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                ]);

                // Redirect to affiliate URL
                wp_redirect($link->affiliate_url, 301);
                exit;
            }
        }
    } catch (Exception $e) {
        error_log('Redirection error: ' . $e->getMessage());
    }
});
