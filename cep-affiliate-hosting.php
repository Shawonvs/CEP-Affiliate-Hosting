<?php
/**
 * Plugin Name: CEP Affiliate Hosting
 * Description: Admin dashboard with Settings, Content Generator, Analytics, and Affiliate Links tabs.
 * Version: 1.2
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

if (file_exists(CEP_AFFILIATE_HOSTING_PATH . 'includes/content-inserter.php')) {
    require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/content-inserter.php';
} else {
    error_log('Missing file: includes/content-inserter.php');
}

if (file_exists(CEP_AFFILIATE_HOSTING_PATH . 'includes/comparison-table.php')) {
    require_once CEP_AFFILIATE_HOSTING_PATH . 'includes/comparison-table.php';
} else {
    error_log('Missing file: includes/comparison-table.php');
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

// --- Affiliate Links Table Creation on Activation ---
register_activation_hook(__FILE__, function () {
    // ...existing code...
    // Create affiliate links table
    global $wpdb;
    $table = $wpdb->prefix . 'cep_affiliate_links';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        url TEXT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description VARCHAR(255) DEFAULT '',
        clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    // ...existing code...
});

// Register admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'CEP Affiliate Hosting',
        'CEP Affiliate Hosting',
        'manage_options',
        'cep-affiliate-hosting',
        'cep_ah_admin_page',
        'dashicons-admin-generic'
    );
    add_submenu_page(
        'cep-affiliate-hosting',
        'Affiliate Links',
        'Affiliate Links',
        'manage_options',
        'cep-affiliate-links',
        'cep_affiliate_links_admin_page'
    );
});

// Enqueue scripts for AJAX tabs
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_cep-affiliate-hosting') return;
    wp_enqueue_script('cep-ah-admin', plugin_dir_url(__FILE__).'js/cep-ah-admin.js', ['jquery'], null, true);
    wp_localize_script('cep-ah-admin', 'cepAhAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('cep_ah_nonce')
    ]);
    wp_enqueue_style('cep-ah-admin', plugin_dir_url(__FILE__).'css/cep-ah-admin.css');
    if ($hook === 'toplevel_page_cep-affiliate-hosting') {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    }
});

// Admin page markup
function cep_ah_admin_page() {
    ?>
    <div class="wrap">
        <h1>CEP Affiliate Hosting</h1>
        <h2 class="nav-tab-wrapper" id="cep-ah-tabs">
            <a href="#" class="nav-tab nav-tab-active" data-tab="settings">Settings</a>
            <a href="#" class="nav-tab" data-tab="content-generator">Content Generator</a>
            <a href="#" class="nav-tab" data-tab="analytics">Analytics</a>
            <a href="#" class="nav-tab" data-tab="affiliate-links">Affiliate Links</a>
        </h2>
        <div id="cep-ah-tab-content">
            <div class="cep-ah-loading">Loading...</div>
        </div>
    </div>
    <?php
}

// AJAX handlers for each tab
add_action('wp_ajax_cep_ah_tab', function() {
    check_ajax_referer('cep_ah_nonce');
    $tab = sanitize_text_field($_POST['tab']);
    switch ($tab) {
        case 'settings':
            cep_ah_render_settings_tab();
            break;
        case 'content-generator':
            cep_ah_render_content_generator_tab();
            break;
        case 'analytics':
            cep_ah_render_analytics_tab();
            break;
        case 'affiliate-links':
            cep_ah_render_affiliate_links_tab();
            break;
        default:
            echo '<p>Invalid tab.</p>';
    }
    wp_die();
});

// Settings API: Register settings
add_action('admin_init', function() {
    register_setting('cep_ah_settings_group', 'cep_ah_option_1');
    add_settings_section('cep_ah_main_section', 'Main Settings', null, 'cep_ah_settings');
    add_settings_field('cep_ah_option_1', 'Option 1', function() {
        $val = esc_attr(get_option('cep_ah_option_1', ''));
        echo "<input type='text' name='cep_ah_option_1' value='$val' />";
    }, 'cep_ah_settings', 'cep_ah_main_section');
});

// Render Settings tab
function cep_ah_render_settings_tab() {
    ?>
    <form method="post" action="options.php">
        <?php
        settings_fields('cep_ah_settings_group');
        do_settings_sections('cep_ah_settings');
        submit_button();
        ?>
    </form>
    <?php
}

// Render Affiliate Links tab (placeholder)
function cep_ah_render_affiliate_links_tab() {
    echo '<h3>Affiliate Links</h3><p>Affiliate links management goes here.</p>';
}

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

// Add AI Content Generator admin page
add_action('admin_menu', function () {
    add_submenu_page(
        'toplevel_page_cep-affiliate-hosting',
        'AI Content Generator',
        'AI Content Generator',
        'manage_options',
        'cep-ai-content-generator',
        'cep_affiliate_hosting_ai_content_generator_page'
    );
});

// Render AI Content Generator page
function cep_affiliate_hosting_ai_content_generator_page() {
    ?>
    <div class="wrap">
        <h1>AI Content Generator</h1>
        <form method="post" action="">
            <?php wp_nonce_field('cep_generate_ai_content', 'cep_ai_content_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cep_keywords">Keywords/Topics</label></th>
                    <td><input type="text" name="cep_keywords" id="cep_keywords" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="cep_generate_content" id="cep_generate_content" class="button button-primary" value="Generate Content">
            </p>
        </form>
        <?php
        if (isset($_POST['cep_generate_content']) && check_admin_referer('cep_generate_ai_content', 'cep_ai_content_nonce')) {
            $keywords = sanitize_text_field($_POST['cep_keywords']);
            $content = cep_affiliate_hosting_generate_ai_content($keywords);
            if ($content) {
                echo '<h2>Generated Content</h2>';
                echo '<textarea readonly rows="10" style="width: 100%;">' . esc_textarea($content) . '</textarea>';
            } else {
                echo '<p style="color: red;">Failed to generate content. Please check the API configuration.</p>';
            }
        }
        ?>
    </div>
    <?php
}

// Generate AI content using OpenAI/Gemini API
function cep_affiliate_hosting_generate_ai_content($keywords) {
    $api_key = get_option('cep_openai_api_key'); // Ensure this option is set in plugin settings
    if (!$api_key) {
        error_log('OpenAI API key is not configured.');
        return false;
    }

    $endpoint = 'https://api.openai.com/v1/completions'; // Update for Gemini if needed
    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'text-davinci-003', // Update model as needed
            'prompt' => "Write a detailed, SEO-optimized blog post about: $keywords. Use the following structure:\n\n"
                . "1. Catchy Headline with Main Keyword\n"
                . "2. Introduction with a call-to-action for an affiliate link\n"
                . "3. Pros and Cons section for hosting services\n"
                . "4. Feature comparison table (if multiple hosting services are mentioned)\n"
                . "5. Conclusion with a strong affiliate call-to-action\n\n"
                . "Ensure the content is engaging, informative, and persuasive.",
            'max_tokens' => 1500,
        ]),
    ]);

    if (is_wp_error($response)) {
        error_log('API request failed: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['choices'][0]['text'] ?? false;
}

// --- Affiliate Links Admin Page Handler ---
function cep_affiliate_links_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'cep_affiliate_links';

    // Handle Add
    if (isset($_POST['cep_aff_add']) && check_admin_referer('cep_aff_add')) {
        $wpdb->insert($table, [
            'url' => esc_url_raw($_POST['url']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_text_field($_POST['description']),
        ]);
        echo '<div class="updated"><p>Affiliate link added.</p></div>';
    }

    // Handle Edit
    if (isset($_POST['cep_aff_edit']) && check_admin_referer('cep_aff_edit')) {
        $wpdb->update($table, [
            'url' => esc_url_raw($_POST['url']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_text_field($_POST['description']),
        ], ['id' => intval($_POST['id'])]);
        echo '<div class="updated"><p>Affiliate link updated.</p></div>';
    }

    // Handle Delete
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete' && check_admin_referer('cep_aff_delete_' . $_GET['id'])) {
        $wpdb->delete($table, ['id' => intval($_GET['id'])]);
        echo '<div class="updated"><p>Affiliate link deleted.</p></div>';
    }

    // List links
    $links = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");

    // Edit form
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", intval($_GET['id'])));
        if ($link) {
            ?>
            <div class="wrap"><h1>Edit Affiliate Link</h1>
            <form method="post">
                <?php wp_nonce_field('cep_aff_edit'); ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($link->id); ?>">
                <table class="form-table">
                    <tr><th>Title</th><td><input name="title" value="<?php echo esc_attr($link->title); ?>" required></td></tr>
                    <tr><th>URL</th><td><input name="url" value="<?php echo esc_attr($link->url); ?>" required></td></tr>
                    <tr><th>Description</th><td><input name="description" value="<?php echo esc_attr($link->description); ?>"></td></tr>
                </table>
                <input type="submit" name="cep_aff_edit" class="button button-primary" value="Update Link">
                <a href="<?php echo admin_url('admin.php?page=cep-affiliate-links'); ?>" class="button">Cancel</a>
            </form>
            </div>
            <?php
            return;
        }
    }

    // Add form and list
    ?>
    <div class="wrap">
        <h1>Affiliate Links</h1>
        <h2>Add New Affiliate Link</h2>
        <form method="post">
            <?php wp_nonce_field('cep_aff_add'); ?>
            <table class="form-table">
                <tr><th>Title</th><td><input name="title" required></td></tr>
                <tr><th>URL</th><td><input name="url" required></td></tr>
                <tr><th>Description</th><td><input name="description"></td></tr>
            </table>
            <input type="submit" name="cep_aff_add" class="button button-primary" value="Add Link">
        </form>
        <h2>All Affiliate Links</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Short URL</th>
                    <th>Clicks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($links as $link): 
                $short_url = home_url('/?cep_aff=' . $link->id);
                $edit_url = wp_nonce_url(admin_url('admin.php?page=cep-affiliate-links&action=edit&id=' . $link->id), 'cep_aff_edit');
                $delete_url = wp_nonce_url(admin_url('admin.php?page=cep-affiliate-links&action=delete&id=' . $link->id), 'cep_aff_delete_' . $link->id);
            ?>
                <tr>
                    <td><?php echo $link->id; ?></td>
                    <td><?php echo esc_html($link->title); ?></td>
                    <td><a href="<?php echo esc_url($short_url); ?>" target="_blank"><?php echo esc_html($short_url); ?></a></td>
                    <td><?php echo intval($link->clicks); ?></td>
                    <td>
                        <a href="<?php echo esc_url($edit_url); ?>">Edit</a> | 
                        <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this link?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// --- Handle Short URL Redirect and Click Tracking ---
add_action('init', function () {
    if (isset($_GET['cep_aff'])) {
        global $wpdb;
        $table = $wpdb->prefix . 'cep_affiliate_links';
        $id = intval($_GET['cep_aff']);
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
        if ($link && !empty($link->url)) {
            $wpdb->query($wpdb->prepare("UPDATE $table SET clicks = clicks + 1 WHERE id = %d", $id));
            wp_redirect($link->url, 301);
            exit;
        }
    }
});

// --- Content Generator Tab (OpenAI) ---
function cep_ah_render_content_generator_tab() {
    ?>
    <h3>Content Generator</h3>
    <form id="cep-ah-content-gen-form">
        <table class="form-table">
            <tr>
                <th><label for="cep_ah_keyword">Keyword/Topic</label></th>
                <td><input type="text" id="cep_ah_keyword" name="keyword" class="regular-text" required></td>
            </tr>
        </table>
        <p>
            <button type="submit" class="button button-primary">Generate Content</button>
        </p>
    </form>
    <div id="cep-ah-content-gen-result"></div>
    <script>
    jQuery(function($){
        $('#cep-ah-content-gen-form').on('submit', function(e){
            e.preventDefault();
            var keyword = $('#cep_ah_keyword').val();
            $('#cep-ah-content-gen-result').html('<div class="cep-ah-loading">Generating content...</div>');
            $.post(cepAhAjax.ajax_url, {
                action: 'cep_ah_generate_content',
                _ajax_nonce: cepAhAjax.nonce,
                keyword: keyword
            }, function(response){
                $('#cep-ah-content-gen-result').html(response);
            });
        });
        // Save as post/page
        $(document).on('submit', '#cep-ah-save-content-form', function(e){
            e.preventDefault();
            var data = $(this).serialize();
            $('#cep-ah-save-content-msg').html('<div class="cep-ah-loading">Saving...</div>');
            $.post(cepAhAjax.ajax_url, data, function(response){
                $('#cep-ah-save-content-msg').html(response);
            });
        });
    });
    </script>
    <?php
}

// AJAX: Generate content using OpenAI
add_action('wp_ajax_cep_ah_generate_content', function() {
    check_ajax_referer('cep_ah_nonce');
    $keyword = sanitize_text_field($_POST['keyword']);
    $content = cep_ah_generate_openai_content($keyword);
    if ($content) {
        ?>
        <h4>Generated Content</h4>
        <textarea rows="12" style="width:100%;" readonly><?php echo esc_textarea($content); ?></textarea>
        <form id="cep-ah-save-content-form">
            <input type="hidden" name="action" value="cep_ah_save_generated_content">
            <input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr(wp_create_nonce('cep_ah_nonce')); ?>">
            <input type="hidden" name="content" value="<?php echo esc_attr($content); ?>">
            <table class="form-table">
                <tr>
                    <th><label for="cep_ah_post_title">Title</label></th>
                    <td><input type="text" name="post_title" id="cep_ah_post_title" class="regular-text" value="<?php echo esc_attr($keyword); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="cep_ah_post_type">Save as</label></th>
                    <td>
                        <select name="post_type" id="cep_ah_post_type">
                            <option value="post">Post</option>
                            <option value="page">Page</option>
                        </select>
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" class="button button-primary">Save to WordPress</button>
            </p>
            <div id="cep-ah-save-content-msg"></div>
        </form>
        <?php
    } else {
        echo '<div style="color:red;">Failed to generate content. Please check your OpenAI API key.</div>';
    }
    wp_die();
});

// AJAX: Save generated content as post/page
add_action('wp_ajax_cep_ah_save_generated_content', function() {
    check_ajax_referer('cep_ah_nonce');
    $title = sanitize_text_field($_POST['post_title']);
    $content = wp_kses_post($_POST['content']);
    $type = ($_POST['post_type'] === 'page') ? 'page' : 'post';
    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'draft',
        'post_type' => $type,
    ]);
    if ($post_id && !is_wp_error($post_id)) {
        $url = admin_url('post.php?action=edit&post=' . $post_id);
        echo '<div style="color:green;">Saved! <a href="'.esc_url($url).'">Edit '.$type.'</a></div>';
    } else {
        echo '<div style="color:red;">Failed to save content.</div>';
    }
    wp_die();
});

// Helper: Generate content using OpenAI API
function cep_ah_generate_openai_content($keyword) {
    $api_key = get_option('cep_openai_api_key');
    if (!$api_key) return false;
    $endpoint = 'https://api.openai.com/v1/completions';
    $prompt = "Write a detailed, SEO-optimized blog post about: $keyword\n\n"
        . "Include:\n"
        . "1. Introduction with a call-to-action for an affiliate link\n"
        . "2. Features section\n"
        . "3. Pros and Cons section\n"
        . "4. Conclusion with a strong affiliate call-to-action\n\n"
        . "Make it engaging, informative, and persuasive.";
    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
            'max_tokens' => 1200,
        ]),
    ]);
    if (is_wp_error($response)) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['choices'][0]['text'] ?? false;
}

// --- Analytics Tab ---
function cep_ah_render_analytics_tab() {
    global $wpdb;
    $table = $wpdb->prefix . 'cep_affiliate_links';

    // Total clicks
    $total_clicks = (int) $wpdb->get_var("SELECT SUM(clicks) FROM $table");

    // Top 5 links
    $top_links = $wpdb->get_results("SELECT title, clicks FROM $table ORDER BY clicks DESC LIMIT 5");

    // Conversion rate (simulate: conversions/clicks, conversions random for demo)
    $total_conversions = 0;
    foreach ($top_links as $l) $total_conversions += rand(0, $l->clicks);
    $conversion_rate = $total_clicks ? round(($total_conversions / $total_clicks) * 100, 2) : 0;

    // Traffic sources (simulate for demo; in real use, track via click log)
    $traffic_sources = [
        'Social' => rand(10, 50),
        'Search' => rand(20, 70),
        'Direct' => rand(5, 30),
    ];

    ?>
    <div class="wrap">
        <h2>Affiliate Analytics</h2>
        <div style="max-width:700px;">
            <canvas id="cep-ah-total-clicks"></canvas>
        </div>
        <div style="max-width:700px;">
            <canvas id="cep-ah-top-links"></canvas>
        </div>
        <div style="max-width:700px;">
            <canvas id="cep-ah-traffic-sources"></canvas>
        </div>
        <div style="margin-top:2em;">
            <strong>Conversion Rate:</strong> <?php echo esc_html($conversion_rate); ?>%
        </div>
    </div>
    <script>
    jQuery(function($){
        // Total Clicks Chart
        new Chart(document.getElementById('cep-ah-total-clicks').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Total Clicks'],
                datasets: [{
                    data: [<?php echo $total_clicks; ?>],
                    backgroundColor: ['#36a2eb'],
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                title: { display: true, text: 'Total Clicks' }
            }
        });

        // Top 5 Links Chart
        new Chart(document.getElementById('cep-ah-top-links').getContext('2d'), {
            type: 'bar',
            data: {
                labels: [<?php foreach($top_links as $l) echo "'" . esc_js($l->title) . "',"; ?>],
                datasets: [{
                    label: 'Clicks',
                    data: [<?php foreach($top_links as $l) echo intval($l->clicks) . ','; ?>],
                    backgroundColor: '#4bc0c0'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                title: { display: true, text: 'Top 5 Affiliate Links' },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Traffic Sources Chart
        new Chart(document.getElementById('cep-ah-traffic-sources').getContext('2d'), {
            type: 'pie',
            data: {
                labels: [<?php foreach($traffic_sources as $k=>$v) echo "'".esc_js($k)."',"; ?>],
                datasets: [{
                    data: [<?php foreach($traffic_sources as $v) echo intval($v).','; ?>],
                    backgroundColor: ['#ff6384','#36a2eb','#ffce56']
                }]
            },
            options: {
                title: { display: true, text: 'Traffic Source Breakdown' }
            }
        });
    });
    </script>
    <?php
}

// --- Shortcode: Display Affiliate Offers as Cards ---
function cep_affiliate_offers_shortcode($atts) {
    global $wpdb;
    $table = $wpdb->prefix . 'cep_affiliate_links';

    // Fetch all offers (assuming 'description' contains features, and 'url' is the affiliate link)
    $offers = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    if (!$offers) {
        return '<div class="cep-affiliate-offers-empty">No affiliate offers available.</div>';
    }

    ob_start();
    ?>
    <div class="cep-affiliate-offers-wrap">
        <?php foreach ($offers as $offer): ?>
            <div class="cep-affiliate-offer-card">
                <div class="cep-affiliate-offer-title"><?php echo esc_html($offer->title); ?></div>
                <?php
                // Try to extract pricing from description if present, e.g. "Price: $X"
                $price = '';
                if (preg_match('/Price:\s*([^\s]+)/i', $offer->description, $m)) {
                    $price = $m[1];
                }
                if ($price): ?>
                    <div class="cep-affiliate-offer-price"><?php echo esc_html($price); ?></div>
                <?php endif; ?>
                <?php
                // Features: split description by newlines or commas
                $features = preg_split('/[\r\n,]+/', $offer->description);
                if ($features && count($features) > 0): ?>
                    <ul class="cep-affiliate-offer-features">
                        <?php foreach ($features as $f):
                            $f = trim($f);
                            if ($f && stripos($f, 'price:') === false): ?>
                                <li><?php echo esc_html($f); ?></li>
                            <?php endif;
                        endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a class="cep-affiliate-offer-cta" href="<?php echo esc_url($offer->url); ?>" target="_blank" rel="nofollow sponsored">View Offer</a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cep_affiliate_offers', 'cep_affiliate_offers_shortcode');

// Enqueue frontend CSS for offers shortcode
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('cep-affiliate-offers', plugin_dir_url(__FILE__) . 'css/cep-affiliate-offers.css');
});
