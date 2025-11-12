<?php
namespace DNS\Admin;

class NotificationAdmin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Directory Notifications',
            'Notifications',
            'manage_options',
            'dns-notifications',
            [$this, 'admin_page'],
            'dashicons-bell',
            25
        );
    }

    public function admin_page() {
        echo '<h1>Directory Notifications Admin</h1>';
        echo '<p>Manage all directory notifications here.</p>';
    }

    public function enqueue_assets() {
        wp_enqueue_style('dns-admin-style', plugin_dir_url(__DIR__, 2) . 'assets/css/admin.css');
        wp_enqueue_script('dns-admin-script', plugin_dir_url(__DIR__, 2) . 'assets/js/admin.js', ['jquery'], false, true);
    }
}
