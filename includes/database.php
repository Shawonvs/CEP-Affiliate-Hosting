<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function cep_affiliate_hosting_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Table for affiliate links
    $links_table = $wpdb->prefix . 'cep_affiliate_links';
    $links_sql = "CREATE TABLE $links_table (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        hosting_name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        affiliate_url TEXT NOT NULL,
        click_count BIGINT(20) UNSIGNED DEFAULT 0
    ) $charset_collate;";

    // Table for click tracking
    $clicks_table = $wpdb->prefix . 'cep_affiliate_clicks';
    $clicks_sql = "CREATE TABLE $clicks_table (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        link_id BIGINT(20) UNSIGNED NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (link_id) REFERENCES $links_table(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($links_sql);
    dbDelta($clicks_sql);
}
