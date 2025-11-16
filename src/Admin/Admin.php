<?php
namespace DNS\Admin;

use DNS\Helper\Messages;

class Admin {

    public function __construct() {
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Register settings
        add_action('admin_init', [$this, 'register_settings']);

        // Add subscribe button to listings
        add_filter('the_content', [$this, 'append_subscribe_button']);
    }

    /**
     * Add admin menu page
     */
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

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'dns_notifications_settings', 'dns_subscribe_job' );
        register_setting( 'dns_notifications_settings', 'dns_subscription_page_job' );
        register_setting( 'dns_notifications_settings', 'dns_subscribe_product' );
        register_setting( 'dns_notifications_settings', 'dns_subscription_page_product' );
        register_setting( 'dns_notifications_settings', 'dns_subscription_page_id' );
    }


       /**
     * Admin Page Content
     */
    public function admin_page() {
        // Get all pages
        $pages = get_pages();

        // Get saved options
        $job_enabled      = get_option('dns_subscribe_job');
        $job_page         = get_option('dns_subscription_page_job');
        $product_enabled  = get_option('dns_subscribe_product');
        $product_page     = get_option('dns_subscription_page_product');
        $selected_page    = get_option('dns_subscription_page_id');

        // Notification Logs
        $logs = get_option('dns_notification_logs', []);
        ?>
        
        <div class="dns-admin-wrap">
            <h1>Directory Notifications Admin</h1>
            <p>Manage all directory notifications here.</p>

            <!-- Tabs Navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#tab-settings" class="nav-tab nav-tab-active">Settings</a>
                <a href="#tab-log" class="nav-tab">Log</a>
                <a href="#tab-subscribed" class="nav-tab">Subscribed Users</a>
            </h2>

            <!-- SETTINGS TAB -->
            <div id="tab-settings" class="tab-content" style="display:block;">
                <form method="post" action="options.php">
                    <?php
                        settings_fields('dns_notifications_settings');
                        do_settings_sections('dns_notifications_settings');
                    ?>

                    <h2>Subscribe Button Settings</h2>

                    <table class="form-table">

                        <!-- JOB LISTINGS -->
                        <tr>
                            <th scope="row">Subscribe Button on Job Listings</th>
                            <td>
                                <label class="dns-toggle-wrapper">
                                    <span class="dns-toggle">
                                        <input type="checkbox" id="dns_subscribe_job" name="dns_subscribe_job" value="1" <?php checked($job_enabled, 1); ?> />
                                        <span class="dns-toggle-slider"></span>
                                    </span>
                                    <span>Enable subscribe button on Job Listing pages</span>
                                </label>

                                <div id="dns_job_page_select" style="margin-top:10px; <?php echo $job_enabled ? '' : 'display:none;'; ?>">
                                    <label>Select Subscription Page:</label>
                                    <select name="dns_subscription_page_job">
                                        <option value="">-- Select Page --</option>
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
                            <th scope="row">Subscribe Button on Product Listings</th>
                            <td>
                                <label class="dns-toggle-wrapper">
                                    <span class="dns-toggle">
                                        <input type="checkbox" id="dns_subscribe_product" name="dns_subscribe_product" value="1" <?php checked($product_enabled, 1); ?> />
                                        <span class="dns-toggle-slider"></span>
                                    </span>
                                    <span>Enable subscribe button on Product Listing pages</span>
                                </label>

                                <div id="dns_product_page_select" style="margin-top:10px; <?php echo $product_enabled ? '' : 'display:none;'; ?>">
                                    <label>Select Subscription Page:</label>
                                    <select name="dns_subscription_page_product">
                                        <option value="">-- Select Page --</option>
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
                            <th scope="row">Default Subscription Page</th>
                            <td>
                                <select name="dns_subscription_page_id">
                                    <option value="">-- Select Page --</option>
                                    <?php foreach ($pages as $page) : ?>
                                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($selected_page, $page->ID); ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Used if no specific page is assigned.</p>
                            </td>
                        </tr>

                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

            <!-- LOG TAB -->
            <div id="tab-log" class="tab-content" style="display:none;">
                <h2>Notification Log</h2>

                <?php if (empty($logs)) : ?>
                    <p>No logs available.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log) : ?>
                                <tr>
                                    <td><?php echo esc_html($log['time']); ?></td>
                                    <td><?php echo esc_html($log['msg']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- SUBSCRIBED USERS TAB -->
            <div id="tab-subscribed" class="tab-content" style="display:none;">
                <h2>Subscribed Users</h2>

                <?php
                $users = get_users(['meta_key' => 'dns_notify_prefs']);

                if (empty($users)) :
                    echo "<p>No subscribed users found.</p>";
                else :
                ?>

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Listing Types</th>
                            <th>Locations</th>
                            <th>Listings</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user) : 
                        $prefs = get_user_meta($user->ID, 'dns_notify_prefs', true);
                        $prefs = wp_parse_args($prefs, [
                            'listing_types' => [],
                            'listing_locations' => [],
                            'listing_posts' => [],
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
                                        if ($term) echo esc_html($term->name) . "<br>";
                                    }
                                } else {
                                    echo "<em>None</em>";
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                if (!empty($prefs['listing_locations'])) {
                                    foreach ($prefs['listing_locations'] as $lid) {
                                        $term = get_term($lid);
                                        if ($term) echo esc_html($term->name) . "<br>";
                                    }
                                } else {
                                    echo "<em>None</em>";
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                if (!empty($prefs['listing_posts'])) {
                                    foreach ($prefs['listing_posts'] as $pid) {
                                        $post = get_post($pid);
                                        if ($post) echo esc_html($post->post_title) . "<br>";
                                    }
                                } else {
                                    echo "<em>None</em>";
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
     * Append Subscribe button to listings
     */
    public function append_subscribe_button($content) {

        // Check post type or archive
        $post_type = get_post_type();
        $is_listing_archive = is_post_type_archive('at_biz_dir');

        if ( ($post_type === 'at_biz_dir' || $is_listing_archive) ) {

            $page_id = get_option('dns_subscription_page_id');
            if (!$page_id) return $content;

            $url = get_permalink($page_id);
            $button_html = '<p><a href="' . esc_url($url) . '" class="button subscribe-notifications">Subscribe to Notifications</a></p>';

            // Append based on settings
            if ( $post_type === 'at_biz_dir' && get_option('dns_subscribe_job') ) {
                $content .= $button_html;
            } elseif ( $is_listing_archive && get_option('dns_subscribe_product') ) {
                $content .= $button_html;
            }
        }

        return $content;
    }
}
