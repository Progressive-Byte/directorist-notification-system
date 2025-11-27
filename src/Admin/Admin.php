<?php
namespace DNS\Admin;

use DNS\Helper\Messages;

defined('ABSPATH') || exit;

/**
 * Class Admin
 *
 * Handles Directory Notifications admin settings, test messages, and subscribe button rendering.
 */
class Admin {

    /**
     * Admin constructor.
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Register settings
        add_action('admin_init', [$this, 'register_settings']);

        // Hook to custom notifications filter
        add_filter('dns_notifications', [$this, 'send_notifications_for_user'], 10, 3);

        // Clear cache when a user is updated or created
        add_action('profile_update', function() {
            delete_transient('dns_cached_users');
        });
        add_action('user_register', function() {
            delete_transient('dns_cached_users');
        });

        // Clear cache when a page is updated or deleted
        add_action('save_post_page', function() {
            delete_transient('dns_cached_pages');
        });
        add_action('delete_post', function($post_id) {
            if (get_post_type($post_id) === 'page') {
                delete_transient('dns_cached_pages');
            }
        });
    }

    /**
     * Add admin submenu under Directorist
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=at_biz_dir', 
            esc_html__('Directory Notifications', 'dns'),
            esc_html__('Notifications', 'dns'), 
            'manage_options', 
            'dns-notifications', 
            [$this, 'admin_page'] 
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        $settings = [
            'dns_subscribe_pages_enabled',
            'dns_job_terms',
            'dns_market_terms',
            'dns_subscription_pages',
            'dns_subscription_page_id',
            'dns_secondary_page_id',
        ];

        foreach ($settings as $setting) {
            register_setting('dns_notifications_settings', $setting);
        }
}


    /**
     * Admin page content
     */
    public function admin_page() {
        $users = dns_get_cached_users();
        $pages = dns_get_cached_pages();

        $job_enabled     = get_option('dns_subscribe_pages_enabled');
        $product_page    = get_option('dns_subscription_page_product');
        $selected_page   = get_option('dns_subscription_page_id');
        ?>

        <div class="dns-admin-wrap">
            <h1><?php esc_html_e('Directory Notifications Admin', 'dns'); ?></h1>
            <p><?php esc_html_e('Manage all directory notifications here.', 'dns'); ?></p>

            <!-- Tabs Navigation -->
            <h2 class="dns-tab-wrapper">
                <a href="#tab-settings" class="dns-tab dns-tab-active">Settings</a>
                <a href="#tab-subscribed" class="dns-tab">Subscribed Users</a>
                <a href="#tab-email-template" class="dns-tab">Email Settings</a>
                <a href="#tab-test-message" class="dns-tab">Test Message</a>
            </h2>

            <!-- Settings Tab -->
            <div id="tab-settings" class="dns-tab-content" style="display:block;">
                <?php
                dns_load_template(
                    DNS_PLUGIN_TEMPLATE . 'Admin/admin-settings-users.php',
                    [
                        'users'           => $users,
                        'pages'           => $pages,
                        'job_enabled'     => $job_enabled,
                        'product_page'    => $product_page,
                        'selected_page'   => $selected_page,
                    ],
                    true
                );
                ?>
            </div>

            <!-- Subscribed Users Tab -->
            <div id="tab-subscribed" class="dns-tab-content" style="display:none;">
                <?php
                $subscribed_users = get_users(['meta_key' => 'dns_notify_prefs']);
                dns_load_template(
                    DNS_PLUGIN_TEMPLATE . 'Admin/admin-subscribed-users.php',
                    ['subscribed_users' => $subscribed_users],
                    true
                );
                ?>
            </div>

            <!-- Email Template Tab -->
            <div id="tab-email-template" class="dns-tab-content" style="display:none;">
                <?php
                dns_load_template(
                    DNS_PLUGIN_TEMPLATE . 'Admin/admin-email-template.php',
                    [],
                    true
                );
                ?>
            </div>

            <!-- Test Message Tab -->
            <div id="tab-test-message" class="dns-tab-content" style="display:none;">
                <?php
                dns_load_template(
                    DNS_PLUGIN_TEMPLATE . 'Admin/admin-test-message.php',
                    ['users' => $users],
                    true
                );
                ?>
            </div>
        </div>

        <?php
    }

    /**
     * Process notifications for users
     *
     * @param array $notifications Array of notification objects
     * @param int   $user_id       User ID
     * @param string $format       Output format ('string' or 'html')
     * @return array
     */
    public function send_notifications_for_user($notifications, $user_id, $format) {
        foreach ($notifications as &$n) {

            // Only process notifications for new listing matches
            if ($n->component_action !== 'new_listing_match') {
                continue;
            }

            $msg  = bp_notifications_get_meta($n->id, 'message', true);
            $link = bp_notifications_get_meta($n->id, 'link', true);

            if ($format === 'string') {
                $n->content = '<a href="' . esc_url($link) . '">' . esc_html($msg) . '</a>';
            }
        }

        return $notifications;
    }
}
