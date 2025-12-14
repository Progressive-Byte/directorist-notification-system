<?php

use DNS\Helper\Messages;
/**
 * Admin Settings Tab Template
 *
 * Variables passed:
 *   $pages          - array of WP_Post objects (all pages)
 *   $job_enabled    - bool (1 or 0)
 *   $job_page       - int (selected job subscription page ID)
 *   $product_enabled- bool (1 or 0)
 *   $product_page   - int (selected product subscription page ID)
 *   $selected_page  - int (default subscription page ID)
 */
?>

<form method="post" action="options.php">
    <?php
    settings_fields('dns_notifications_settings');
    do_settings_sections('dns_notifications_settings');
    ?>

    <h2><?php esc_html_e('Subscribe Button Settings', 'directorist-notification-system'); ?></h2>

    <table class="form-table">

        <!-- Notification Preferences Page -->
        <tr>
            <th scope="row">
                <?php esc_html_e('Market Place Page', 'directorist-notification-system'); ?>
                <span style="color:#e11d48;font-weight:bold;">*</span>
            </th>
            <td>
                <select name="dns_subscription_page_id">
                    <option value="">-- <?php esc_html_e('Select Page', 'directorist-notification-system'); ?> --</option>
                    <?php foreach ($pages as $page) : ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($selected_page, $page->ID); ?>>
                            <?php echo esc_html($page->post_title . ' (ID: ' . $page->ID . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Select your market place page.', 'directorist-notification-system'); ?></p>
            </td>
        </tr>

        <!-- Secondary Notification Page -->
        <tr>
            <th scope="row">
                <?php esc_html_e('Job Listing page', 'directorist-notification-system'); ?>
            </th>

            <td>
                <?php
                $secondary_page = get_option('dns_secondary_page_id', '');
                ?>

                <select name="dns_secondary_page_id">
                    <option value="">
                        -- <?php esc_html_e('Select Page', 'directorist-notification-system'); ?> --
                    </option>

                    <?php foreach ($pages as $page) : ?>
                        <option value="<?php echo esc_attr($page->ID); ?>"
                            <?php selected($secondary_page, $page->ID); ?>>
                            <?php echo esc_html($page->post_title . ' (ID: ' . $page->ID . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <p class="description">
                    <?php esc_html_e('Job Listing page.', 'directorist-notification-system'); ?>
                </p>
            </td>
        </tr>


        <!-- Market Place Listing Type -->
        <tr>
            <th scope="row"><?php esc_html_e('Market Place Listing Type', 'directorist-notification-system'); ?>
                <span style="color:#e11d48;font-weight:bold;">*</span>
            </th>
            <td>
                <?php
                $directory_types = get_terms(array(
                    'taxonomy'   => ATBDP_DIRECTORY_TYPE,
                    'hide_empty' => false,
                ));
                $selected_market_term = get_option('dns_market_terms', '');
                if (is_array($selected_market_term)) {
                    $selected_market_term = reset($selected_market_term);
                }
                ?>
                <select name="dns_market_terms">
                    <option value="">-- <?php esc_html_e('Select Market Place Type', 'directorist-notification-system'); ?> --</option>
                    <?php foreach ($directory_types as $type) : ?>
                        <option value="<?php echo esc_attr($type->term_id); ?>" <?php selected($selected_market_term, $type->term_id); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <!-- Job Listing Type -->
        <tr>
            <th scope="row"><?php esc_html_e('Job Listing Type', 'directorist-notification-system'); ?>
                <span style="color:#e11d48;font-weight:bold;">*</span>
            </th>
            <td>
                <?php
                $selected_job_term = get_option('dns_job_terms', '');
                if (is_array($selected_job_term)) {
                    $selected_job_term = reset($selected_job_term);
                }
                ?>
                <select name="dns_job_terms">
                    <option value="">-- <?php esc_html_e('Select Job Listing Type', 'directorist-notification-system'); ?> --</option>
                    <?php foreach ($directory_types as $type) : ?>
                        <option value="<?php echo esc_attr($type->term_id); ?>" <?php selected($selected_job_term, $type->term_id); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <!-- Notification Button -->
        <tr>
            <th scope="row"><?php esc_html_e('Show Subscribe Button', 'directorist-notification-system'); ?></th>
            <td>
                <label class="dns-toggle-wrapper">
                    <span class="dns-toggle">
                        <input type="checkbox" id="dns_subscribe_pages" name="dns_subscribe_pages_enabled" value="1" <?php checked( $job_enabled , 1 ); ?> />
                        <span class="dns-toggle-slider"></span>
                    </span>
                    <span><?php esc_html_e('Enable subscribe button on selected pages', 'directorist-notification-system'); ?></span>
                </label>

                <div id="dns_pages_select" style="margin-top:10px; <?php echo ($job_enabled ) ? '' : 'display:none;'; ?>">
                    <label><?php esc_html_e('Select pages to enable subscribe button:', 'directorist-notification-system'); ?></label>
                    <div style="margin-top:5px; max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:5px;">
                        <?php
                        $saved_pages = get_option('dns_subscription_pages', []);
                        $saved_pages = is_array($saved_pages) ? array_map('intval', $saved_pages) : [];

                        foreach ($pages as $page) :
                            $is_checked = in_array((int) $page->ID, $saved_pages, true);
                        ?>
                            <label style="display:block; margin-bottom:3px;">
                                <input
                                    type="checkbox"
                                    name="dns_subscription_pages[]"
                                    value="<?php echo esc_attr($page->ID); ?>"
                                    <?php checked($is_checked); ?>
                                >
                                <?php echo esc_html($page->post_title . ' (ID: ' . $page->ID . ')'); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

            </td>
        </tr>

    </table>

    <?php submit_button(); ?>
</form>
