<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle short URL redirection and track clicks.
 */
function cep_affiliate_hosting_handle_redirect() {
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
                'referrer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : null,
            ]);

            // Redirect to affiliate URL
            wp_redirect($link->affiliate_url, 301);
            exit;
        }
    }
}

add_action('init', 'cep_affiliate_hosting_handle_redirect');
