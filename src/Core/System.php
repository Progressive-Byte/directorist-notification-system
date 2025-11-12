<?php
namespace DNS\Core;

class System {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'init', [ $this, 'init_classes' ] );       
    }

    public function init_classes() {
        if ( is_admin() ) {
            new \DNS\Admin\Admin();
        } else {
            new \DNS\Frontend\Frontend();
            new \DNS\Frontend\Shortcode();
        }
    }

    public function enqueue_scripts() {
        // Frontend CSS + JS
        wp_enqueue_style(
            'dns-notification-style',
            DNS_ASSETS_URL . 'css/frontend.css',
            [],
            DNS_VERSION
        );

        wp_enqueue_script(
            'dns-notification-script',
            DNS_ASSETS_URL . 'js/frontend.js',
            [ 'jquery' ],
            DNS_VERSION,
            true
        );
    }

    public function enqueue_admin_scripts() {
        // Admin CSS + JS (optional)
        wp_enqueue_style(
            'dns-admin-style',
            DNS_ASSETS_URL . 'css/admin.css',
            [],
            DNS_VERSION
        );

        wp_enqueue_script(
            'dns-admin-script',
            DNS_ASSETS_URL . 'js/admin.js',
            [ 'jquery' ],
            DNS_VERSION,
            true
        );
    }
}
