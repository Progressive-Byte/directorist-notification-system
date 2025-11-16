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

        // Append subscribe button to listings
        add_filter('the_content', [$this, 'send_notifications_for_user']);


        // Clear cache when a user is updated
        add_action('profile_update', function() {
            delete_transient('dns_cached_users');
        });
        add_action('user_register', function() {
            delete_transient('dns_cached_users');
        });

        // Clear cache when a page is updated
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
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            esc_html__('Directory Notifications', 'dns'),
            esc_html__('Notifications', 'dns'),
            'manage_options',
            'dns-notifications',
            [$this, 'admin_page'],
            'dashicons-bell',
            25
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('dns_notifications_settings', 'dns_subscribe_job');
        register_setting('dns_notifications_settings', 'dns_subscription_page_job');
        register_setting('dns_notifications_settings', 'dns_subscribe_product');
        register_setting('dns_notifications_settings', 'dns_subscription_page_product');
        register_setting('dns_notifications_settings', 'dns_subscription_page_id');
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        $users = dns_get_cached_users();
        $pages = dns_get_cached_pages();

        $job_enabled     = get_option('dns_subscribe_job');
        $job_page        = get_option('dns_subscription_page_job');
        $product_enabled = get_option('dns_subscribe_product');
        $product_page    = get_option('dns_subscription_page_product');
        $selected_page   = get_option('dns_subscription_page_id');
        ?>

        <div class="dns-admin-wrap">
            <h1><?php esc_html_e('Directory Notifications Admin', 'dns'); ?></h1>
            <p><?php esc_html_e('Manage all directory notifications here.', 'dns'); ?></p>

            <!-- Tabs Navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#tab-settings" class="nav-tab "><?php esc_html_e('Settings', 'dns'); ?></a>
                <a href="#tab-test-message" class="nav-tab"><?php esc_html_e('Test Message', 'dns'); ?></a>
                <a href="#tab-subscribed" class="nav-tab"><?php esc_html_e('Subscribed Users', 'dns'); ?></a>
            </h2>

            <div id="tab-settings" class="tab-content" style="display:block;">
                <?php
                dns_load_template(
                    DNS_PLUGIN_DIR . 'src/Template/Admin/admin-settings-users.php',
                    [
                        'users'           => $users,
                        'pages'           => $pages,
                        'job_enabled'     => $job_enabled,
                        'job_page'        => $job_page,
                        'product_enabled' => $product_enabled,
                        'product_page'    => $product_page,
                        'selected_page'   => $selected_page,
                    ],
                    true
                );

                ?>

            </div>
           

            <!-- TEST MESSAGE TAB -->
            <div id="tab-test-message" class="tab-content" style="display:none;">
                <?php
                dns_load_template(
                    DNS_PLUGIN_DIR . 'src/Template/Admin/admin-test-message.php',
                    [ 'users' => $users ],
                    true
                );
                ?>
            </div>

            <!-- SUBSCRIBED USERS TAB -->
            <div id="tab-subscribed" class="tab-content" style="display:none;">
                <?php
                $subscribed_users = get_users( [ 'meta_key' => 'dns_notify_prefs' ] );
                dns_load_template(
                    DNS_PLUGIN_DIR . 'src/Template/Admin/admin-subscribed-users.php',
                    [ 'subscribed_users' => $subscribed_users ],
                    true
                );
                ?>
            </div>
        </div>

        <?php
    }

    /**
     * Append subscribe button to listings
     *
     * @param string $content
     * @return string
     */
    public function send_notifications_for_user( $notifications, $user_id, $format ){
          foreach ( $notifications as &$n ) {

            // Only our notification
            if ( $n->component_action !== 'new_listing_match' ) {
                continue;
            }

            $msg  = bp_notifications_get_meta( $n->id, 'message', true );
            $link = bp_notifications_get_meta( $n->id, 'link', true );

            if ( $format === 'string' ) {
                $n->content = '<a href="' . esc_url( $link ) . '">' . esc_html( $msg ) . '</a>';
            }
        }

        return $notifications;  
    }
}
