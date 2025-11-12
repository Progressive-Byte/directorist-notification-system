<?php
/**
 * Plugin Name: Directorist Notification System
 * Plugin URI: https://techwithmahbub.com/
 * Description: A notification system plugin for WordPress directories.
 * Version: 1.0.0
 * Author: Mahbub
 * Author URI: https://techwithmahbub.com/
 * Text Domain: directorist-notification-system
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Plugin constants
if ( ! defined( 'DNS_VERSION' ) ) {
    define( 'DNS_VERSION', '1.0.0' );
}

if ( ! defined( 'DNS_PLUGIN_FILE' ) ) {
    define( 'DNS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'DNS_PLUGIN_DIR' ) ) {
    define( 'DNS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DNS_PLUGIN_URL' ) ) {
    define( 'DNS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'DNS_TEXT_DOMAIN' ) ) {
    define( 'DNS_TEXT_DOMAIN', 'directorist-notification-system' );
}

if ( ! defined( 'DNS_ASSETS_URL' ) ) {
	define( 'DNS_ASSETS_URL', DNS_PLUGIN_URL . 'assets/' );    
}

// Composer autoload
if ( file_exists( DNS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require DNS_PLUGIN_DIR . 'vendor/autoload.php';
}

// Initialize plugin
add_action('plugins_loaded', function() {
    DNS\Core\System::get_instance();
});
