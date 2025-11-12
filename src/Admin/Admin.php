<?php
namespace DNS\Admin;

class Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
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
}
