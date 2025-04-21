<?php
/*
Plugin Name: CEP Affiliate Links
Description: Manage affiliate links with short URLs and click tracking.
Version: 1.0
Author: Your Name
*/

// On activation: create custom table
register_activation_hook(__FILE__, 'cl_activate_plugin');
function cl_activate_plugin(){
    global $wpdb;
    $table = $wpdb->prefix . 'affiliate_links';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url text NOT NULL,
        title varchar(200) NOT NULL,
        description varchar(255) NOT NULL,
        clicks bigint(20) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id)
    ) $charset;";
    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Add admin menu
add_action('admin_menu','cl_register_menu');
function cl_register_menu(){
    add_menu_page('Affiliate Links','Affiliate Links','manage_options','affiliate-links','cl_render_admin_page');
}

// Render CRUD page
function cl_render_admin_page(){
    global $wpdb;
    $table = $wpdb->prefix . 'affiliate_links';
    // Handle add
    if($_POST['cl_action']=='add' && check_admin_referer('cl_add')){
        $wpdb->insert($table,[
            'url'=>esc_url_raw($_POST['url']),
            'title'=>sanitize_text_field($_POST['title']),
            'description'=>sanitize_text_field($_POST['description'])
        ]);
        echo '<div class="updated">Link added.</div>';
    }
    // Handle edit
    if($_POST['cl_action']=='edit' && check_admin_referer('cl_edit')){
        $wpdb->update($table,
            ['url'=>esc_url_raw($_POST['url']),
             'title'=>sanitize_text_field($_POST['title']),
             'description'=>sanitize_text_field($_POST['description'])],
            ['id'=>intval($_POST['id'])]
        );
        echo '<div class="updated">Link updated.</div>';
    }
    // Handle delete
    if(isset($_GET['action']) && $_GET['action']=='delete' && check_admin_referer('cl_delete_'.$_GET['id'])){
        $wpdb->delete($table,['id'=>intval($_GET['id'])]);
        echo '<div class="updated">Link deleted.</div>';
    }
    $links = $wpdb->get_results("SELECT * FROM $table");
    ?>
    <div class="wrap"><h1>Affiliate Links</h1>
    <h2>Add New</h2>
    <form method="post">
        <?php wp_nonce_field('cl_add'); ?>
        <input type="hidden" name="cl_action" value="add"/>
        <table class="form-table">
            <tr><th>Title</th><td><input name="title" required/></td></tr>
            <tr><th>URL</th><td><input name="url" required/></td></tr>
            <tr><th>Description</th><td><input name="description"/></td></tr>
        </table>
        <?php submit_button('Add Link'); ?>
    </form>
    <h2>Existing Links</h2>
    <table class="widefat fixed"><thead><tr><th>ID</th><th>Title</th><th>Short URL</th><th>Clicks</th><th>Actions</th></tr></thead><tbody>
    <?php foreach($links as $link):
        $short = home_url('/?aff='.$link->id);
        $editurl   = add_query_arg(['page'=>'affiliate-links','action'=>'edit','id'=>$link->id],admin_url('admin.php'));
        $deleteurl = wp_nonce_url(add_query_arg(['page'=>'affiliate-links','action'=>'delete','id'=>$link->id],admin_url('admin.php')),'cl_delete_'.$link->id);
    ?>
        <tr>
            <td><?php echo $link->id; ?></td>
            <td><?php echo esc_html($link->title); ?></td>
            <td><a href="<?php echo esc_url($short); ?>" target="_blank"><?php echo esc_html($short); ?></a></td>
            <td><?php echo $link->clicks; ?></td>
            <td><a href="<?php echo esc_url($editurl); ?>">Edit</a> | <a href="<?php echo esc_url($deleteurl); ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
    <?php if(isset($_GET['action']) && $_GET['action']=='edit'):
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$_GET['id']));
        if($link): ?>
        <h2>Edit Link</h2>
        <form method="post">
            <?php wp_nonce_field('cl_edit'); ?>
            <input type="hidden" name="cl_action" value="edit"/>
            <input type="hidden" name="id" value="<?php echo $link->id; ?>"/>
            <table class="form-table">
                <tr><th>Title</th><td><input name="title" value="<?php echo esc_attr($link->title); ?>" required/></td></tr>
                <tr><th>URL</th><td><input name="url" value="<?php echo esc_attr($link->url); ?>" required/></td></tr>
                <tr><th>Description</th><td><input name="description" value="<?php echo esc_attr($link->description); ?>"/></td></tr>
            </table>
            <?php submit_button('Update Link'); ?>
        </form>
        <?php endif;
    endif; ?>
    <?php cl_render_affiliate_analysis_section(); ?>
    </div>
    <?php
}

// Catch short URLs, count click and redirect
add_action('init','cl_handle_redirect');
function cl_handle_redirect(){
    if(isset($_GET['aff'])){
        global $wpdb;
        $id    = intval($_GET['aff']);
        $table = $wpdb->prefix . 'affiliate_links';
        $link  = $wpdb->get_row($wpdb->prepare("SELECT url FROM $table WHERE id=%d",$id));
        if($link){
            $wpdb->query($wpdb->prepare("UPDATE $table SET clicks=clicks+1 WHERE id=%d",$id));
            wp_redirect($link->url);
            exit;
        }
    }
}

/**
 * Analyze affiliate link performance and suggest optimizations.
 * - Returns: array with best links, optimal posting time, and competitor info.
 */
function cl_analyze_affiliate_performance() {
    global $wpdb;
    $table = $wpdb->prefix . 'affiliate_links';

    // Get all links and clicks
    $links = $wpdb->get_results("SELECT * FROM $table ORDER BY clicks DESC");

    // Suggest best-performing links (top 3 by clicks)
    $best_links = array_slice($links, 0, 3);

    // Analyze click timestamps for optimal posting time (requires click log table)
    // For now, simulate with random suggestion
    $optimal_time = 'Tuesday 10:00 AM'; // Placeholder, implement with real data if available

    // Competitor offers (manual/API entry in future)
    $competitors = get_option('cl_competitor_offers', []);

    return [
        'best_links' => $best_links,
        'optimal_time' => $optimal_time,
        'competitors' => $competitors,
    ];
}

// Example admin page section to show analysis (call this in your admin page if needed)
function cl_render_affiliate_analysis_section() {
    $analysis = cl_analyze_affiliate_performance();
    ?>
    <h2>Affiliate Link Performance Analysis</h2>
    <h3>Top Performing Links</h3>
    <ol>
        <?php foreach ($analysis['best_links'] as $link): ?>
            <li>
                <strong><?php echo esc_html($link->title); ?></strong>
                (<?php echo intval($link->clicks); ?> clicks)
                <br>
                <a href="<?php echo esc_url($link->url); ?>" target="_blank"><?php echo esc_html($link->url); ?></a>
            </li>
        <?php endforeach; ?>
    </ol>
    <h3>Recommended Posting Time</h3>
    <p><?php echo esc_html($analysis['optimal_time']); ?></p>
    <h3>Competitor Hosting Offers</h3>
    <?php if (!empty($analysis['competitors'])): ?>
        <ul>
            <?php foreach ($analysis['competitors'] as $comp): ?>
                <li>
                    <strong><?php echo esc_html($comp['name']); ?></strong>:
                    <?php echo esc_html($comp['offer']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No competitor data entered yet.</p>
    <?php endif; ?>
    <?php
}

// --- Settings Section for API Credentials ---
add_action('admin_menu', function() {
    add_options_page(
        'Affiliate API Settings',
        'Affiliate API Settings',
        'manage_options',
        'affiliate-api-settings',
        'cl_render_affiliate_api_settings_page'
    );
});

function cl_render_affiliate_api_settings_page() {
    ?>
    <div class="wrap">
        <h1>Affiliate API Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cl_affiliate_api_settings');
            do_settings_sections('cl_affiliate_api_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('cl_affiliate_api_settings', 'cl_hostinger_api_key');
    register_setting('cl_affiliate_api_settings', 'cl_bluehost_api_key');
    add_settings_section('cl_aff_api_section', 'API Credentials', null, 'cl_affiliate_api_settings');
    add_settings_field('cl_hostinger_api_key', 'Hostinger API Key', function() {
        echo '<input type="text" name="cl_hostinger_api_key" value="' . esc_attr(get_option('cl_hostinger_api_key')) . '" class="regular-text">';
    }, 'cl_affiliate_api_settings', 'cl_aff_api_section');
    add_settings_field('cl_bluehost_api_key', 'Bluehost API Key', function() {
        echo '<input type="text" name="cl_bluehost_api_key" value="' . esc_attr(get_option('cl_bluehost_api_key')) . '" class="regular-text">';
    }, 'cl_affiliate_api_settings', 'cl_aff_api_section');
});

// --- Fetch and Sync Offers from API ---
function cl_fetch_affiliate_offers() {
    $offers = [];

    // Example: Hostinger API (pseudo endpoint)
    $hostinger_key = get_option('cl_hostinger_api_key');
    if ($hostinger_key) {
        $response = wp_remote_get('https://api.hostinger.com/v1/offers?api_key=' . urlencode($hostinger_key));
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['offers'])) {
                foreach ($data['offers'] as $offer) {
                    $offers[] = [
                        'program' => 'Hostinger',
                        'name' => $offer['name'],
                        'price' => $offer['price'],
                        'link' => $offer['affiliate_link'],
                        'desc' => $offer['description'] ?? '',
                    ];
                }
            }
        }
    }

    // Example: Bluehost API (pseudo endpoint)
    $bluehost_key = get_option('cl_bluehost_api_key');
    if ($bluehost_key) {
        $response = wp_remote_get('https://api.bluehost.com/v1/offers?api_key=' . urlencode($bluehost_key));
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['offers'])) {
                foreach ($data['offers'] as $offer) {
                    $offers[] = [
                        'program' => 'Bluehost',
                        'name' => $offer['name'],
                        'price' => $offer['price'],
                        'link' => $offer['affiliate_link'],
                        'desc' => $offer['description'] ?? '',
                    ];
                }
            }
        }
    }

    // Save offers to option for display
    update_option('cl_affiliate_offers', $offers);
}

// --- Cron Job: Sync Offers Daily ---
if (!wp_next_scheduled('cl_sync_affiliate_offers_daily')) {
    wp_schedule_event(time(), 'daily', 'cl_sync_affiliate_offers_daily');
}
add_action('cl_sync_affiliate_offers_daily', 'cl_fetch_affiliate_offers');

// --- Shortcode to Display Offers ---
add_shortcode('affiliate_offers', function($atts) {
    $offers = get_option('cl_affiliate_offers', []);
    if (empty($offers)) return '<p>No offers available.</p>';
    ob_start();
    ?>
    <div class="affiliate-offers-table">
        <table>
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Link</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($offers as $offer): ?>
                <tr>
                    <td><?php echo esc_html($offer['program']); ?></td>
                    <td><?php echo esc_html($offer['name']); ?></td>
                    <td><?php echo esc_html($offer['desc']); ?></td>
                    <td><?php echo esc_html($offer['price']); ?></td>
                    <td><a href="<?php echo esc_url($offer['link']); ?>" target="_blank" rel="nofollow">View Offer</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
});

// --- Gutenberg Block Registration (optional, basic) ---
add_action('init', function() {
    if (function_exists('register_block_type')) {
        register_block_type('cep/affiliate-offers', [
            'render_callback' => function() {
                return do_shortcode('[affiliate_offers]');
            },
            'attributes' => [],
            'editor_script' => null,
        });
    }
});
