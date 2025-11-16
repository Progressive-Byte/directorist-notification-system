<?php
namespace DNS\Admin;

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
     * Admin page content
     */
    public function admin_page() {
        // Get all pages
        $pages = get_pages();

        // Get saved options
        $job_enabled      = get_option('dns_subscribe_job');
        $job_page         = get_option('dns_subscription_page_job');
        $product_enabled  = get_option('dns_subscribe_product');
        $product_page     = get_option('dns_subscription_page_product');
        $selected_page    = get_option('dns_subscription_page_id'); // for generic subscription page if needed
        ?>
        <div class="dns-admin-wrap">
            <h1>Directory Notifications Admin</h1>
            <p>Manage all directory notifications here.</p>

            <!-- Tabs Navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#tab-settings" class="nav-tab nav-tab-active">Settings</a>
                <a href="#tab-log" class="nav-tab">Log</a>
            </h2>

            <!-- Tabs Content -->
            <div id="tab-settings" class="tab-content" style="display:block;">
                <form method="post" action="options.php">
                    <?php
                        settings_fields('dns_notifications_settings');
                        do_settings_sections('dns_notifications_settings');
                    ?>

                    <h2>Subscribe Button Settings</h2>

                    <table class="form-table">
                        <!-- Job Listings -->
                        <tr valign="top">
                            <th scope="row">Add "Subscribe to Notifications" button on Job Listings page</th>
                            <td>
                                <label class="dns-toggle-wrapper">
                                    <span class="dns-toggle">
                                        <input type="checkbox" id="dns_subscribe_job" name="dns_subscribe_job" value="1" <?php checked(1, $job_enabled, true); ?> />
                                        <span class="dns-toggle-slider"></span>
                                    </span>
                                    <span>Enable subscribe button on Job Listing pages</span>
                                </label>

                                <!-- Conditional Page Select -->
                                <div id="dns_job_page_select" style="margin-top:10px; <?php echo $job_enabled ? '' : 'display:none;'; ?>">
                                    <label>Select subscription page for Job Listings:</label>
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

                        <!-- Product Listings -->
                        <tr valign="top">
                            <th scope="row">Add "Subscribe to Notifications" button on Product Listings page</th>
                            <td>
                                <label class="dns-toggle-wrapper">
                                    <span class="dns-toggle">
                                        <input type="checkbox" id="dns_subscribe_product" name="dns_subscribe_product" value="1" <?php checked(1, $product_enabled, true); ?> />
                                        <span class="dns-toggle-slider"></span>
                                    </span>
                                    <span>Enable subscribe button on Product Listing pages</span>
                                </label>

                                <!-- Conditional Page Select -->
                                <div id="dns_product_page_select" style="margin-top:10px; <?php echo $product_enabled ? '' : 'display:none;'; ?>">
                                    <label>Select subscription page for Product Listings:</label>
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

                        <!-- General Subscription Page -->
                        <tr valign="top">
                            <th scope="row">Subscription Page</th>
                            <td>
                                <select name="dns_subscription_page_id">
                                    <option value="">-- Select Page --</option>
                                    <?php foreach ($pages as $page) : ?>
                                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($selected_page, $page->ID); ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Select the page where users will be redirected when clicking the "Subscribe to Notifications" button.</p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

            <!-- Log Tab -->
            <div id="tab-log" class="tab-content" style="display:none;">
                <h2>Notification Log</h2>
                <p>Here you can view the notification logs in future.</p>
                <div id="dns-log-output">
                    <!-- Log content can be added dynamically -->
                </div>
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
