<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Automatically insert affiliate links into WordPress posts.
 *
 * @param string $content The post content.
 * @return string The modified content with affiliate links.
 */
function cep_insert_affiliate_links($content) {
    if (is_singular('post')) {
        global $wpdb;

        $links_table = $wpdb->prefix . 'cep_affiliate_links';
        $links = $wpdb->get_results("SELECT hosting_name, slug, affiliate_url FROM $links_table");

        if (!empty($links)) {
            foreach ($links as $link) {
                $keyword = preg_quote($link->hosting_name, '/');
                $affiliate_link = '<a href="' . esc_url(home_url('/' . $link->slug)) . '" target="_blank" rel="nofollow">' . esc_html($link->hosting_name) . '</a>';

                // Replace the first occurrence of the keyword only
                $content = preg_replace('/\b' . $keyword . '\b(?![^<]*<\/a>)/', $affiliate_link, $content, 1);
            }
        }
    }

    return $content;
}

add_filter('the_content', 'cep_insert_affiliate_links');
