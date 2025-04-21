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
    ?>
    <div class="wrap">
        <h1>CEP Affiliate Hosting</h1>
        <h2 class="nav-tab-wrapper">
            <a href="#" class="nav-tab nav-tab-active" data-tab="settings">Settings</a>
            <a href="#" class="nav-tab" data-tab="content">Content Generator</a>
            <a href="#" class="nav-tab" data-tab="analytics">Analytics</a>
            <a href="#" class="nav-tab" data-tab="links">Affiliate Links</a>
        </h2>
        <div id="cep-tab-content">
            <p>Loading...</p>
        </div>
    </div>
    <?php
}

add_action('wp_ajax_cep_load_tab_content', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $tab = sanitize_text_field($_POST['tab']);
    switch ($tab) {
        case 'settings':
            // Render settings tab content
            ?>
            <form method="POST">
                <h2>Settings</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="cep_openai_api_key">OpenAI API Key</label></th>
                        <td><input type="text" name="cep_openai_api_key" id="cep_openai_api_key" value="<?php echo esc_attr(get_option('cep_openai_api_key', '')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <p><input type="submit" class="button button-primary" value="Save Settings"></p>
            </form>
            <?php
            break;

        case 'content':
            // Render content generator tab content
            ?>
            <form method="POST">
                <h2>Content Generator</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="topic">Topic</label></th>
                        <td><input type="text" name="topic" id="topic" class="regular-text"></td>
                    </tr>
                </table>
                <p><input type="submit" class="button button-primary" value="Generate Content"></p>
            </form>
            <?php
            break;

        case 'analytics':
            // Render analytics tab content
            ?>
            <h2>Analytics</h2>
            <p>Analytics data will be displayed here.</p>
            <?php
            break;

        case 'links':
            // Render affiliate links tab content
            ?>
            <h2>Affiliate Links</h2>
            <p>Manage your affiliate links here.</p>
            <?php
            break;

        default:
            wp_send_json_error('Invalid tab', 400);
    }

    wp_die();
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('cep-admin-tabs', CEP_AFFILIATE_HOSTING_URL . 'assets/js/admin-tabs.js', ['jquery'], '1.0.0', true);
    wp_localize_script('cep-admin-tabs', 'cepAjax', [
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cep_admin_nonce'),
    ]);
});

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
