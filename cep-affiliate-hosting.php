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
