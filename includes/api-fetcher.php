<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fetch affiliate links from supported hosting providers.
 *
 * @param string $provider The hosting provider (e.g., 'bluehost', 'hostinger', 'siteground').
 * @param string $api_key The API key for the hosting provider.
 * @return array|WP_Error List of affiliate links or WP_Error on failure.
 */
function cep_fetch_affiliate_links($provider, $api_key) {
    $api_urls = [
        'bluehost' => 'https://api.bluehost.com/affiliate/links',
        'hostinger' => 'https://api.hostinger.com/affiliate/links',
        'siteground' => 'https://api.siteground.com/affiliate/links',
    ];

    if (!isset($api_urls[$provider])) {
        return new WP_Error('invalid_provider', 'Unsupported hosting provider.');
    }

    $response = wp_remote_get($api_urls[$provider], [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
        ],
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return new WP_Error('api_error', 'Failed to fetch affiliate links. HTTP Status: ' . $status_code);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', 'Failed to parse API response.');
    }

    return $data;
}

/**
 * Store fetched affiliate links in the database.
 *
 * @param array $links List of affiliate links.
 * @return void
 */
function cep_store_affiliate_links($links) {
    global $wpdb;

    $links_table = $wpdb->prefix . 'cep_affiliate_links';

    foreach ($links as $link) {
        $wpdb->insert($links_table, [
            'hosting_name' => sanitize_text_field($link['hosting_name']),
            'slug' => sanitize_title($link['slug']),
            'affiliate_url' => esc_url_raw($link['affiliate_url']),
        ]);
    }
}
