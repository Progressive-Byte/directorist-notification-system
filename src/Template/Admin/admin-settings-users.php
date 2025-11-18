<?php
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

    <h2><?php esc_html_e('Subscribe Button Settings', 'dns'); ?></h2>

    <table class="form-table">
        <tr>
            <th scope="row">
                <?php esc_html_e('Notification Preferences Page', 'dns'); ?>
                <span style="color:#e11d48;font-weight:bold;">*</span>
            </th>
            <td>
                <select name="dns_subscription_page_id">
                    <option value="">-- <?php esc_html_e('Select Page', 'dns'); ?> --</option>
                    <?php foreach ($pages as $page) : ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($selected_page, $page->ID); ?>>
                            <?php echo esc_html($page->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Select your notification preferences page.', 'dns'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php esc_html_e('Market Place Listing Type', 'dns'); ?></th>
            <td>
                <?php
                $directory_types = get_terms(array(
                    'taxonomy'   => ATBDP_DIRECTORY_TYPE,
                    'hide_empty' => false,
                ));

                // Get saved option and ensure it's a single value
                $selected_market_term = get_option('dns_market_terms', '');
                if (is_array($selected_market_term)) {
                    $selected_market_term = reset($selected_market_term);
                }
                ?>

                <select name="dns_market_terms">
                    <option value="">-- <?php esc_html_e('Select Market Place Type', 'dns'); ?> --</option>
                    <?php foreach ($directory_types as $type) : ?>
                        <option value="<?php echo esc_attr($type->term_id); ?>" <?php selected($selected_market_term, $type->term_id); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php esc_html_e('Job Listing Type', 'dns'); ?></th>
            <td>
                <?php
                $directory_types = get_terms(array(
                    'taxonomy'   => ATBDP_DIRECTORY_TYPE,
                    'hide_empty' => false,
                ));

                // Get saved option and ensure it's a single value
                $selected_job_term = get_option('dns_job_terms', '');
                if (is_array($selected_job_term)) {
                    $selected_job_term = reset($selected_job_term);
                }
                ?>

                <select name="dns_job_terms">
                    <option value="">-- <?php esc_html_e('Select Job Listing Type', 'dns'); ?> --</option>
                    <?php foreach ($directory_types as $type) : ?>
                        <option value="<?php echo esc_attr($type->term_id); ?>" <?php selected($selected_job_term, $type->term_id); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>





        <!-- JOB LISTINGS -->
        <tr>
            <th scope="row"><?php esc_html_e('Job Listings page', 'dns'); ?></th>
            <td>
                <label class="dns-toggle-wrapper">
                    <span class="dns-toggle">
                        <input type="checkbox" id="dns_subscribe_job" name="dns_subscribe_job" value="1" <?php checked($job_enabled, 1); ?> />
                        <span class="dns-toggle-slider"></span>
                    </span>
                    <span><?php esc_html_e('Enable subscribe button on Job Listing pages', 'dns'); ?></span>
                </label>

                <div id="dns_job_page_select" style="margin-top:10px; <?php echo $job_enabled ? '' : 'display:none;'; ?>">
                    <label><?php esc_html_e('Select your job listing page:', 'dns'); ?></label>
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
            <th scope="row"><?php esc_html_e('Product Listings page', 'dns'); ?></th>
            <td>
                <label class="dns-toggle-wrapper">
                    <span class="dns-toggle">
                        <input type="checkbox" id="dns_subscribe_product" name="dns_subscribe_product" value="1" <?php checked($product_enabled, 1); ?> />
                        <span class="dns-toggle-slider"></span>
                    </span>
                    <span><?php esc_html_e('Enable subscribe button on Product Listing pages', 'dns'); ?></span>
                </label>

                <div id="dns_product_page_select" style="margin-top:10px; <?php echo $product_enabled ? '' : 'display:none;'; ?>">
                    <label><?php esc_html_e('Select your product listing page:', 'dns'); ?></label>
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
        
    </table>

    <?php submit_button(); ?>
</form>
