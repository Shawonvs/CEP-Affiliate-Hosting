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
    $clicks_table = $wpdb->prefix . 'cep_affiliate_clicks';
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

    // Inline styles for responsiveness
    echo '<style>
        @media (max-width: 768px) {
            .widefat {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .form-table th, .form-table td {
                display: block;
                width: 100%;
            }
            .form-table input, .form-table select {
                width: 100%;
                box-sizing: border-box;
            }
            .tablenav-pages {
                text-align: center;
            }
        }
    </style>';

    // Pagination setup
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $links_per_page = 10;
    $offset = ($current_page - 1) * $links_per_page;

    // Sorting setup
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'click_count';
    $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'DESC';

    // Fetch total links count
    $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $links_table");

    // Fetch links with sorting and pagination
    $links = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $links_table ORDER BY $orderby $order LIMIT %d OFFSET %d",
        $links_per_page,
        $offset
    ));

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cep_save_api_keys'])) {
        check_admin_referer('cep_save_api_keys_action', 'cep_save_api_keys_nonce');

        $bluehost_api_key = sanitize_text_field($_POST['bluehost_api_key']);
        $hostinger_api_key = sanitize_text_field($_POST['hostinger_api_key']);
        $siteground_api_key = sanitize_text_field($_POST['siteground_api_key']);

        update_option('cep_bluehost_api_key', $bluehost_api_key);
        update_option('cep_hostinger_api_key', $hostinger_api_key);
        update_option('cep_siteground_api_key', $siteground_api_key);

        echo '<div class="updated"><p>API keys saved successfully.</p></div>';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cep_generate_content'])) {
        check_admin_referer('cep_generate_content_action', 'cep_generate_content_nonce');

        $topic = sanitize_text_field($_POST['topic']);
        $keywords = sanitize_text_field($_POST['keywords']);
        $api_key = get_option('cep_openai_api_key', '');

        if (empty($api_key)) {
            echo '<div class="error"><p>OpenAI API key is not configured. Please add it in the API Integration tab.</p></div>';
        } else {
            $content = cep_generate_blog_post($topic, $keywords, $api_key);
            if (is_wp_error($content)) {
                echo '<div class="error"><p>Failed to generate content: ' . esc_html($content->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="updated"><p>Content generated successfully. You can copy it below:</p></div>';
                echo '<textarea rows="10" style="width:100%;">' . esc_textarea($content) . '</textarea>';
            }
        }
    }

    $bluehost_api_key = get_option('cep_bluehost_api_key', '');
    $hostinger_api_key = get_option('cep_hostinger_api_key', '');
    $siteground_api_key = get_option('cep_siteground_api_key', '');

    ?>
    <div class="wrap">
        <h1>CEP Affiliate Hosting</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=cep-affiliate-hosting&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=cep-affiliate-hosting&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">API Integration</a>
            <a href="?page=cep-affiliate-hosting&tab=analytics" class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">Analytics</a>
            <a href="?page=cep-affiliate-hosting&tab=content" class="nav-tab <?php echo $active_tab === 'content' ? 'nav-tab-active' : ''; ?>">Content Generator</a>
            <a href="?page=cep-affiliate-hosting&tab=dashboard" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">Dashboard</a>
        </h2>

        <?php if ($active_tab === 'settings'): ?>
            <form method="POST">
                <h2>Manage Affiliate Links</h2>
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
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><a href="?page=cep-affiliate-hosting&tab=settings&orderby=hosting_name&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>">Hosting Name</a></th>
                        <th><a href="?page=cep-affiliate-hosting&tab=settings&orderby=slug&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>">Slug</a></th>
                        <th><a href="?page=cep-affiliate-hosting&tab=settings&orderby=affiliate_url&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>">Affiliate URL</a></th>
                        <th><a href="?page=cep-affiliate-hosting&tab=settings&orderby=click_count&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>">Clicks</a></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($links)): ?>
                        <tr>
                            <td colspan="5">No links found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($links as $link): ?>
                            <tr>
                                <td><?php echo esc_html($link->hosting_name); ?></td>
                                <td><?php echo esc_html($link->slug); ?></td>
                                <td><a href="<?php echo esc_url($link->affiliate_url); ?>" target="_blank"><?php echo esc_url($link->affiliate_url); ?></a></td>
                                <td><?php echo intval($link->click_count); ?></td>
                                <td>
                                    <a href="?page=cep-affiliate-hosting&action=edit&id=<?php echo intval($link->id); ?>" class="button">Edit</a>
                                    <a href="?page=cep-affiliate-hosting&action=delete&id=<?php echo intval($link->id); ?>" class="button button-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            // Pagination links
            $total_pages = ceil($total_links / $links_per_page);
            if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php elseif ($active_tab === 'api'): ?>
            <form method="POST">
                <?php wp_nonce_field('cep_save_api_keys_action', 'cep_save_api_keys_nonce'); ?>
                <h2>API Keys</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="bluehost_api_key">Bluehost API Key</label></th>
                        <td><input type="text" name="bluehost_api_key" id="bluehost_api_key" value="<?php echo esc_attr($bluehost_api_key); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="hostinger_api_key">Hostinger API Key</label></th>
                        <td><input type="text" name="hostinger_api_key" id="hostinger_api_key" value="<?php echo esc_attr($hostinger_api_key); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="siteground_api_key">SiteGround API Key</label></th>
                        <td><input type="text" name="siteground_api_key" id="siteground_api_key" value="<?php echo esc_attr($siteground_api_key); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <p><input type="submit" name="cep_save_api_keys" class="button button-primary" value="Save API Keys"></p>
            </form>
        <?php elseif ($active_tab === 'analytics'): ?>
            <h2>Affiliate Link Analytics</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Hosting Name</th>
                        <th>Slug</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $analytics = $wpdb->get_results("SELECT hosting_name, slug, click_count FROM $links_table ORDER BY click_count DESC");
                    if (empty($analytics)) {
                        echo '<tr><td colspan="3">No analytics data available.</td></tr>';
                    } else {
                        foreach ($analytics as $data) {
                            echo '<tr>';
                            echo '<td>' . esc_html($data->hosting_name) . '</td>';
                            echo '<td>' . esc_html($data->slug) . '</td>';
                            echo '<td>' . intval($data->click_count) . '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        <?php elseif ($active_tab === 'content'): ?>
            <form method="POST">
                <?php wp_nonce_field('cep_generate_content_action', 'cep_generate_content_nonce'); ?>
                <h2>Generate Blog Post</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="topic">Topic</label></th>
                        <td><input type="text" name="topic" id="topic" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="keywords">Keywords</label></th>
                        <td><input type="text" name="keywords" id="keywords" placeholder="e.g., hosting, WordPress, SEO" class="regular-text"></td>
                    </tr>
                </table>
                <p><input type="submit" name="cep_generate_content" class="button button-primary" value="Generate Content"></p>
            </form>
        <?php elseif ($active_tab === 'dashboard'): ?>
            <h2>Affiliate Link Performance</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Hosting Name</th>
                        <th>Slug</th>
                        <th>Clicks</th>
                        <th>Top Referrer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $links = $wpdb->get_results("SELECT * FROM $links_table");
                    if (empty($links)) {
                        echo '<tr><td colspan="4">No data available.</td></tr>';
                    } else {
                        foreach ($links as $link) {
                            $top_referrer = $wpdb->get_var($wpdb->prepare(
                                "SELECT referrer FROM $clicks_table WHERE link_id = %d AND referrer IS NOT NULL GROUP BY referrer ORDER BY COUNT(*) DESC LIMIT 1",
                                $link->id
                            ));
                            echo '<tr>';
                            echo '<td>' . esc_html($link->hosting_name) . '</td>';
                            echo '<td>' . esc_html($link->slug) . '</td>';
                            echo '<td>' . intval($link->click_count) . '</td>';
                            echo '<td>' . ($top_referrer ? esc_url($top_referrer) : 'N/A') . '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// Ensure missing file is handled gracefully
if (!file_exists(plugin_dir_path(__FILE__) . '../includes/tracker.php')) {
    error_log('Missing file: includes/tracker.php');
} else {
    include_once plugin_dir_path(__FILE__) . '../includes/tracker.php';
}

add_action('admin_init', function () {
    register_setting('cep_affiliate_hosting_settings', 'cep_openai_api_key');

    add_settings_section(
        'cep_ai_settings_section',
        'AI Content Generator Settings',
        function () {
            echo '<p>Configure the OpenAI API key for generating AI content.</p>';
        },
        'cep_affiliate_hosting_settings'
    );

    add_settings_field(
        'cep_openai_api_key',
        'OpenAI API Key',
        function () {
            $api_key = get_option('cep_openai_api_key', '');
            echo '<input type="text" name="cep_openai_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        },
        'cep_affiliate_hosting_settings',
        'cep_ai_settings_section'
    );
});
