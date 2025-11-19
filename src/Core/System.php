<?php
namespace DNS\Core;

if (!defined('ABSPATH')) exit;

class System {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Enqueue assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ]);
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

        // Initialize classes
        add_action('init', [$this, 'init_classes']);
    }

    /**
     * Initialize Admin or Frontend classes
     */
    public function init_classes() {
        if (is_admin()) {
            if (class_exists('\DNS\Admin\Admin')) {
                new \DNS\Admin\Admin();
            }
        } else {
            if (class_exists('\DNS\Frontend\Frontend')) {
                new \DNS\Frontend\Frontend();
            }
            if (class_exists('\DNS\Frontend\Shortcode')) {
                new \DNS\Frontend\Shortcode();
            }
        }

        if (class_exists('\DNS\Common\Common')) {
            new \DNS\Common\Common();
        }


    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('dashicons');
        // Frontend CSS
        wp_enqueue_style(
            'dns-notification-style',
            DNS_ASSETS_URL . 'css/frontend.css',
            [],
            DNS_VERSION
        );

        // Frontend JS
        wp_enqueue_script(
            'dns-notification-script',
            DNS_ASSETS_URL . 'js/frontend.js',
            ['jquery'],
            DNS_VERSION,
            true
        );

        // $this->enqueue_select2();
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_style(
            'dns-admin-style',
            DNS_ASSETS_URL . 'css/admin.css',
            [],
            DNS_VERSION
        );

        wp_enqueue_script(
            'dns-admin-script',
            DNS_ASSETS_URL . 'js/admin.js',
            ['jquery'],
            DNS_VERSION,
            true
        );

        // $this->enqueue_select2();
    }

    /**
     * Enqueue Select2 safely, avoid duplicates
     */
    private function enqueue_select2() {
        if (!wp_script_is('select2-js', 'enqueued')) {
            wp_enqueue_script(
                'select2-js',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                ['jquery'],
                '4.1.0',
                true
            );
        }

        if (!wp_style_is('select2-css', 'enqueued')) {
            wp_enqueue_style(
                'select2-css',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                [],
                '4.1.0'
            );
        }
    }
}
