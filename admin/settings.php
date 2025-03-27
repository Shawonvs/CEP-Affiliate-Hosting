<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function cep_affiliate_hosting_add_admin_menu() {
    add_menu_page(
        'Affiliate Hosting',
        'Affiliate Hosting',
        'manage_options',
        'cep-affiliate-hosting',
        'cep_affiliate_hosting_settings_page',
        'dashicons-admin-links',
        20
    );
}

function cep_affiliate_hosting_settings_page() {
    global $wpdb;

    if (!$wpdb) {
        error_log('Critical error: $wpdb is not available.');
        echo '<div class="error"><p>Database connection error. Please check your WordPress installation.</p></div>';
        return;
    }

    $links_table = $wpdb->prefix . 'cep_affiliate_links';

    $search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $query = "SELECT * FROM $links_table";
    if (!empty($search_query)) {
        $query .= $wpdb->prepare(" WHERE hosting_name LIKE %s OR slug LIKE %s", "%$search_query%", "%$search_query%");
    }

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cep_add_link'])) {
            $hosting_name = isset($_POST['hosting_name']) ? sanitize_text_field($_POST['hosting_name']) : '';
            $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
            $affiliate_url = isset($_POST['affiliate_url']) ? esc_url_raw($_POST['affiliate_url']) : '';

            if (!empty($hosting_name) && !empty($slug) && !empty($affiliate_url)) {
                $inserted = $wpdb->insert($links_table, [
                    'hosting_name' => $hosting_name,
                    'slug' => $slug,
                    'affiliate_url' => $affiliate_url,
                ]);

                if ($inserted === false) {
                    error_log('Failed to insert affiliate link: ' . $wpdb->last_error);
                    echo '<div class="error"><p>Failed to add the link. Please try again.</p></div>';
                }
            } else {
                echo '<div class="error"><p>All fields are required.</p></div>';
            }
        }

        $links = $wpdb->get_results($query);
        if ($links === false) {
            error_log('Failed to fetch affiliate links: ' . $wpdb->last_error);
            $links = [];
        }
    } catch (Exception $e) {
        error_log('Unexpected error in settings page: ' . $e->getMessage());
        echo '<div class="error"><p>An unexpected error occurred. Please check the logs.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Affiliate Hosting Links</h1>
        <form method="GET">
            <input type="hidden" name="page" value="cep-affiliate-hosting">
            <input type="text" name="search" placeholder="Search links..." value="<?php echo esc_attr($search_query); ?>">
            <button type="submit" class="button">Search</button>
        </form>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th><label for="hosting_name">Hosting Name</label></th>
                    <td><input type="text" name="hosting_name" id="hosting_name" required></td>
                </tr>
                <tr>
                    <th><label for="slug">Slug</label></th>
                    <td><input type="text" name="slug" id="slug" required></td>
                </tr>
                <tr>
                    <th><label for="affiliate_url">Affiliate URL</label></th>
                    <td><input type="url" name="affiliate_url" id="affiliate_url" required></td>
                </tr>
            </table>
            <p><input type="submit" name="cep_add_link" class="button button-primary" value="Add Link"></p>
        </form>
        <h2>Existing Links</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Hosting Name</th>
                    <th>Slug</th>
                    <th>Affiliate URL</th>
                    <th>Clicks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($links)): ?>
                    <tr>
                        <td colspan="4">No links found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                        <tr></tr>
                            <td><?php echo esc_html($link->hosting_name); ?></td>
                            <td><?php echo esc_html($link->slug); ?></td>
                            <td><?php echo esc_url($link->affiliate_url); ?></td>
                            <td><?php echo intval($link->click_count); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
