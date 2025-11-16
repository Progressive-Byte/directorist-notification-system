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
        $pages = get_pages();
        $users = get_users();

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
                <a href="#tab-settings" class="nav-tab nav-tab-active"><?php esc_html_e('Settings', 'dns'); ?></a>
                <a href="#tab-test-message" class="nav-tab"><?php esc_html_e('Test Message', 'dns'); ?></a>
                <a href="#tab-subscribed" class="nav-tab"><?php esc_html_e('Subscribed Users', 'dns'); ?></a>
            </h2>

            <!-- SETTINGS TAB -->
            <div id="tab-settings" class="tab-content" style="display:block;">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('dns_notifications_settings');
                    do_settings_sections('dns_notifications_settings');
                    ?>

                    <h2><?php esc_html_e('Subscribe Button Settings', 'dns'); ?></h2>

                    <table class="form-table">
                        <!-- JOB LISTINGS -->
                        <tr>
                            <th scope="row"><?php esc_html_e('Subscribe Button on Job Listings', 'dns'); ?></th>
                            <td>
                                <label class="dns-toggle-wrapper">
                                    <span class="dns-toggle">
                                        <input type="checkbox" id="dns_subscribe_job" name="dns_subscribe_job" value="1" <?php checked($job_enabled, 1); ?> />
                                        <span class="dns-toggle-slider"></span>
                                    </span>
                                    <span><?php esc_html_e('Enable subscribe button on Job Listing pages', 'dns'); ?></span>
                                </label>

                                <div id="dns_job_page_select" style="margin-top:10px; <?php echo $job_enabled ? '' : 'display:none;'; ?>">
                                    <label><?php esc_html_e('Select Subscription Page:', 'dns'); ?></label>
                                    <select name="dns_subscription_page_job">
                                        <option value="">-- <?php esc_html_e('Select Page', 'dns'); ?> --</option>
                                        <?php foreach ($pages as $page) : ?>
                                            <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($job_page, $page->ID); ?>>
                                                <?php echo esc_html($page->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                        </tr>

                        <!-- PRODUCT LISTINGS -->
                        <tr>
                            <th scope="row"><?php esc_html_e('Subscribe Button on Product Listings', 'dns'); ?></th>
                            <td>
                                <label class="dns-toggle-wrapper">
                                    <span class="dns-toggle">
                                        <input type="checkbox" id="dns_subscribe_product" name="dns_subscribe_product" value="1" <?php checked($product_enabled, 1); ?> />
                                        <span class="dns-toggle-slider"></span>
                                    </span>
                                    <span><?php esc_html_e('Enable subscribe button on Product Listing pages', 'dns'); ?></span>
                                </label>

                                <div id="dns_product_page_select" style="margin-top:10px; <?php echo $product_enabled ? '' : 'display:none;'; ?>">
                                    <label><?php esc_html_e('Select Subscription Page:', 'dns'); ?></label>
                                    <select name="dns_subscription_page_product">
                                        <option value="">-- <?php esc_html_e('Select Page', 'dns'); ?> --</option>
                                        <?php foreach ($pages as $page) : ?>
                                            <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($product_page, $page->ID); ?>>
                                                <?php echo esc_html($page->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                        </tr>

                        <!-- GENERAL SUB PAGE -->
                        <tr>
                            <th scope="row"><?php esc_html_e('Default Subscription Page', 'dns'); ?></th>
                            <td>
                                <select name="dns_subscription_page_id">
                                    <option value="">-- <?php esc_html_e('Select Page', 'dns'); ?> --</option>
                                    <?php foreach ($pages as $page) : ?>
                                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($selected_page, $page->ID); ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Used if no specific page is assigned.', 'dns'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

            <!-- TEST MESSAGE TAB -->
            <div id="tab-test-message" class="tab-content" style="display:none;">
                <h2><?php esc_html_e('Test Message', 'dns'); ?></h2>

                <form method="post">
                    <table class="form-table">
                        <tr>
                            <th><label for="user_id"><?php esc_html_e('Select User', 'dns'); ?></label></th>
                            <td>
                                <select name="user_id" id="user_id" required>
                                    <option value="">-- <?php esc_html_e('Select User', 'dns'); ?> --</option>
                                    <?php foreach ($users as $user) : ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>">
                                            <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="message"><?php esc_html_e('Message', 'dns'); ?></label></th>
                            <td>
                                <textarea name="message" id="message" rows="5" cols="50" required></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Send Method', 'dns'); ?></th>
                            <td>
                                <label><input type="checkbox" name="send_email" value="1"> <?php esc_html_e('Email', 'dns'); ?></label><br>
                                <label><input type="checkbox" name="send_bp" value="1"> <?php esc_html_e('BuddyPress Notification', 'dns'); ?></label>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <input type="submit" name="test_message_submit" class="button button-primary" value="<?php esc_attr_e('Send Test', 'dns'); ?>">
                    </p>
                </form>
            </div>

            <!-- SUBSCRIBED USERS TAB -->
            <div id="tab-subscribed" class="tab-content" style="display:none;">
                <h2><?php esc_html_e('Subscribed Users', 'dns'); ?></h2>

                <?php
                $subscribed_users = get_users(['meta_key' => 'dns_notify_prefs']);

                if (empty($subscribed_users)) :
                    echo '<p>' . esc_html__('No subscribed users found.', 'dns') . '</p>';
                else :
                ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('User', 'dns'); ?></th>
                            <th><?php esc_html_e('Listing Types', 'dns'); ?></th>
                            <th><?php esc_html_e('Locations', 'dns'); ?></th>
                            <th><?php esc_html_e('Listings', 'dns'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribed_users as $user) :
                            $prefs = get_user_meta($user->ID, 'dns_notify_prefs', true);
                            $prefs = wp_parse_args($prefs, [
                                'listing_types'     => [],
                                'listing_locations' => [],
                                'listing_posts'     => [],
                            ]);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                <small><?php echo esc_html($user->user_email); ?></small>
                            </td>
                            <td>
                                <?php
                                if (!empty($prefs['listing_types'])) {
                                    foreach ($prefs['listing_types'] as $tid) {
                                        $term = get_term($tid);
                                        if ($term) {
                                            echo esc_html($term->name) . '<br>';
                                        }
                                    }
                                } else {
                                    echo '<em>' . esc_html__('None', 'dns') . '</em>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($prefs['listing_locations'])) {
                                    foreach ($prefs['listing_locations'] as $lid) {
                                        $term = get_term($lid);
                                        if ($term) {
                                            echo esc_html($term->name) . '<br>';
                                        }
                                    }
                                } else {
                                    echo '<em>' . esc_html__('None', 'dns') . '</em>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($prefs['listing_posts'])) {
                                    foreach ($prefs['listing_posts'] as $pid) {
                                        $post = get_post($pid);
                                        if ($post) {
                                            echo esc_html($post->post_title) . '<br>';
                                        }
                                    }
                                } else {
                                    echo '<em>' . esc_html__('None', 'dns') . '</em>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
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
