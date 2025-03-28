<?php
/**
 * Plugin Name: Debug Helper
 * Description: A helper plugin to enable debugging and log errors.
 * Version: 1.0
 * Author: Your Name
 */

// Enable WordPress debugging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Log a custom message to debug.log
add_action('init', function () {
    error_log('Debug Helper plugin initialized.');
});
