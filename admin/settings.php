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
    $links_table = $wpdb->prefix . 'cep_affiliate_links';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cep_add_link'])) {
        $hosting_name = sanitize_text_field($_POST['hosting_name']);
        $slug = sanitize_title($_POST['slug']);
        $affiliate_url = esc_url_raw($_POST['affiliate_url']);

        $wpdb->insert($links_table, [
            'hosting_name' => $hosting_name,
            'slug' => $slug,
            'affiliate_url' => $affiliate_url,
        ]);
    }

    $links = $wpdb->get_results("SELECT * FROM $links_table");

    ?>
    <div class="wrap">
        <h1>Affiliate Hosting Links</h1>
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
                <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo esc_html($link->hosting_name); ?></td>
                        <td><?php echo esc_html($link->slug); ?></td>
                        <td><?php echo esc_url($link->affiliate_url); ?></td>
                        <td><?php echo intval($link->click_count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
