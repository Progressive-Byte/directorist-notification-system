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
        register_setting('dns_notifications_settings', 'dns_subscribe_job');
        register_setting('dns_notifications_settings', 'dns_subscribe_product');
        register_setting('dns_notifications_settings', 'dns_subscription_page_id');
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        $pages = get_pages();
        $selected_page = get_option('dns_subscription_page_id');

        // Determine active tab
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        ?>
        <div class="dns-admin-wrap">
            <h1>Directory Notifications Admin</h1>

            <!-- Tabs -->
            <h2 class="nav-tab-wrapper">
                <a href="?page=dns-notifications&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
                <a href="?page=dns-notifications&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
                <!-- Add more tabs here if needed -->
            </h2>

            <!-- Tab Content -->
            <div class="tab-content">
                <?php if ($active_tab === 'settings') : ?>
                    <form method="post" action="options.php">
                        <?php
                            settings_fields('dns_notifications_settings');
                            do_settings_sections('dns_notifications_settings');
                        ?>

                        <h2>Subscribe Button Settings</h2>

                        <table class="form-table">
                            <!-- Job Listings Toggle -->
                            <tr valign="top">
                                <th scope="row">Add "Subscribe to Notifications" button on Job Listings page</th>
                                <td>
                                    <label class="dns-toggle-wrapper">
                                        <span class="dns-toggle">
                                            <input type="checkbox" name="dns_subscribe_job" value="1" <?php checked(1, get_option('dns_subscribe_job'), true); ?> />
                                            <span class="dns-toggle-slider"></span>
                                        </span>
                                        <span>Enable subscribe button on Job Listing pages</span>
                                    </label>
                                </td>
                            </tr>

                            <!-- Product Listings Toggle -->
                            <tr valign="top">
                                <th scope="row">Add "Subscribe to Notifications" button on Product Listings page</th>
                                <td>
                                    <label class="dns-toggle-wrapper">
                                        <span class="dns-toggle">
                                            <input type="checkbox" name="dns_subscribe_product" value="1" <?php checked(1, get_option('dns_subscribe_product'), true); ?> />
                                            <span class="dns-toggle-slider"></span>
                                        </span>
                                        <span>Enable subscribe button on Product Listing pages</span>
                                    </label>
                                </td>
                            </tr>

                            <!-- Subscription Page Selector -->
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

                <?php elseif ($active_tab === 'logs') : ?>
                    <h2>Notification Logs</h2>
                    <p>Here you can see the recent notification activity (future implementation).</p>
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
