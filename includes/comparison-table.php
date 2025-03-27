<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate a comparison table for hosting providers.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML for the comparison table.
 */
function cep_generate_comparison_table($atts) {
    global $wpdb;

    $links_table = $wpdb->prefix . 'cep_affiliate_links';

    // Default attributes
    $atts = shortcode_atts([
        'providers' => '', // Comma-separated list of hosting provider slugs
    ], $atts, 'cep_comparison_table');

    $provider_slugs = array_map('sanitize_title', explode(',', $atts['providers']));
    $placeholders = implode(',', array_fill(0, count($provider_slugs), '%s'));

    // Fetch hosting provider data
    $query = $wpdb->prepare(
        "SELECT hosting_name, slug, affiliate_url, click_count FROM $links_table WHERE slug IN ($placeholders)",
        $provider_slugs
    );
    $providers = $wpdb->get_results($query);

    if (empty($providers)) {
        return '<p>No hosting providers found for the specified slugs.</p>';
    }

    // Generate the comparison table
    ob_start();
    ?>
    <table class="cep-comparison-table">
        <thead>
            <tr>
                <th>Hosting Provider</th>
                <th>Affiliate Link</th>
                <th>Clicks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($providers as $provider): ?>
                <tr>
                    <td><?php echo esc_html($provider->hosting_name); ?></td>
                    <td><a href="<?php echo esc_url(home_url('/' . $provider->slug)); ?>" target="_blank" rel="nofollow">Visit</a></td>
                    <td><?php echo intval($provider->click_count); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .cep-comparison-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cep-comparison-table th, .cep-comparison-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .cep-comparison-table th {
            background-color: #f4f4f4;
        }
    </style>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('cep_comparison_table', 'cep_generate_comparison_table');
