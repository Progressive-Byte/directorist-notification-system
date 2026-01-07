<?php
use DNS\Helper\Messages;

/** @var array $data */
$data = is_array( $data ?? null ) ? $data : [];

/**
 * Extract saved data safely
 */
$listing_types     = $data['listing_types']['listing']   ?? [];
$listing_locations = $data['listing_types']['locations'] ?? [];

// Messages::pri( $data );
?>

<div class="dns-wrap">
    <div class="dns-card">

        <h3 class="dns-title">
            <?php esc_html_e( 'Notification Preferences', 'directorist-notification-system' ); ?>
        </h3>

        <p class="dns-sub">
            <?php esc_html_e(
                'Choose which listing types and locations you want updates for.',
                'directorist-notification-system'
            ); ?>
        </p>

        <form method="post">

            <?php wp_nonce_field( 'np_save_prefs', 'np_nonce' ); ?>

            <?php
            $selected_market_term = (int) get_option( 'dns_market_terms', '' );
            $selected_job_term    = (int) get_option( 'dns_job_terms', '' );

            $market_types_list = ! empty( $selected_market_term )
                ? dns_get_term_objects_by_directory( $selected_market_term )
                : [];

            $job_types_list = ! empty( $selected_job_term )
                ? dns_get_term_objects_by_directory( $selected_job_term )
                : [];
            ?>

            <!-- ============================= -->
            <!-- Tabs Navigation -->
            <!-- ============================= -->
            <div class="dns-tabs">
                <button type="button" class="dns-tab" data-tab="job">
                    <?php esc_html_e( 'Job Listing', 'directorist-notification-system' ); ?>
                </button>

                <button type="button" class="dns-tab" data-tab="locations">
                    <?php esc_html_e( 'Location', 'directorist-notification-system' ); ?>
                </button>
            </div>

            <?php
            /**
             * Checkbox renderer
             */
            function dns_render_checkbox_block( $items, $saved_items, $empty_message, $name_attr ) {
                if ( empty( $items ) ) {
                    echo '<p>' . esc_html( $empty_message ) . '</p>';
                    return;
                }
                ?>

                <div class="dns-search-wrapper">
                    <input type="text"
                        class="dns-search-input"
                        placeholder="<?php esc_attr_e( 'Search...', 'directorist-notification-system' ); ?>"
                    >
                    <span class="dns-search-clear">×</span>

                    <button type="button" class="dns-btn dns-btn--mini dns-select-all">
                        <?php esc_html_e( 'Select All', 'directorist-notification-system' ); ?>
                    </button>

                    <button type="button" class="dns-btn dns-btn--mini dns-deselect-all">
                        <?php esc_html_e( 'Deselect All', 'directorist-notification-system' ); ?>
                    </button>
                </div>

                <div class="dns-checkbox-list">
                    <?php
                    $i = 1;
                    foreach ( $items as $item ) :
                        $checked = in_array( $item->term_id, $saved_items, true );
                        ?>
                        <label class="dns-checkbox <?php echo $checked ? 'dns-checked' : ''; ?>">
                            <input type="checkbox"
                                name="<?php echo esc_attr( $name_attr ); ?>[]"
                                value="<?php echo esc_attr( $item->term_id ); ?>"
                                <?php checked( $checked ); ?>
                            >
                            <?php echo esc_html( $i . '. ' . $item->name ); ?>
                        </label>
                        <?php
                        $i++;
                    endforeach;
                    ?>
                </div>
            <?php
            }
            ?>

            <!-- ============================= -->
            <!-- JOB TAB -->
            <!-- ============================= -->
            <div class="dns-tab-content" id="tab-job">
                <?php
                dns_render_checkbox_block(
                    $job_types_list,
                    $listing_types,
                    __( 'Please select Job listing.', 'directorist-notification-system' ),
                    'listing_types'
                );
                ?>
            </div>

            <!-- ============================= -->
            <!-- LOCATION TAB -->
            <!-- ============================= -->
            <div class="dns-tab-content" id="tab-locations">
                <?php
                dns_render_checkbox_block(
                    $locations,
                    $listing_locations,
                    __( 'No locations available.', 'directorist-notification-system' ),
                    'listing_locations'
                );
                ?>
            </div>

            <!-- ============================= -->
            <!-- FORM ACTIONS -->
            <!-- ============================= -->
            <div class="dns-actions">
                <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">
                    <?php esc_html_e( 'Confirm', 'directorist-notification-system' ); ?>
                </button>
            </div>

        </form>

        <?php
        // DEBUG (optional)
        // Messages::pri( $data );
        ?>

    </div>
</div>
