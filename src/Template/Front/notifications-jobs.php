<?php
use DNS\Helper\Messages;

?>

<div class="dns-wrap">
    <div class="dns-card">

        <h3 class="dns-title">
            <?php esc_html_e( 'Notification Preferences', 'directorist-notification-system' ); ?>
        </h3>

        <p class="dns-sub">
            <?php esc_html_e( 'Choose which listing types or listings and locations you want updates for.', 'directorist-notification-system' ); ?>
        </p>

        <form method="post">

            <?php wp_nonce_field( 'np_save_prefs', 'np_nonce' ); ?>

            <?php
            $selected_market_term = (int) get_option( 'dns_market_terms', '' );
            $selected_job_term    = (int) get_option( 'dns_job_terms', '' );

            $market_types   = ! empty( $selected_market_term ) ? dns_get_term_objects_by_directory( $selected_market_term ) : array();
            $job_types      = ! empty( $selected_job_term )    ? dns_get_term_objects_by_directory( $selected_job_term )    : array();
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
             * Renders the checklist block
             */
            function dns_render_checkbox_block( $items, $saved_items, $empty_message, $name_attr ) {
                ?>

                <?php if ( empty( $items ) ) : ?>

                    <p><?php echo esc_html( $empty_message ); ?></p>

                <?php else : ?>

                    <div class="dns-search-wrapper" style="display:flex; gap:10px; margin-bottom:10px;">
                        <input type="text"
                            class="dns-search-input"
                            placeholder="<?php esc_attr_e( 'Search...', 'directorist-notification-system' ); ?>"
                            style="flex:1;"
                        >

                        <button type="button" class="dns-btn dns-btn--mini dns-select-all">
                            <?php esc_html_e( 'Select All', 'directorist-notification-system' ); ?>
                        </button>

                        <button type="button" class="dns-btn dns-btn--mini dns-deselect-all">
                            <?php esc_html_e( 'Deselect All', 'directorist-notification-system' ); ?>
                        </button>

                        <button type="button" class="dns-btn dns-btn--mini dns-show-selected">
                            <span class="dns-show-selected-icon">👁️</span>
                            <span class="dns-show-selected-text">
                                <?php esc_html_e( 'Show Selected', 'directorist-notification-system' ); ?>
                            </span>
                        </button>
                    </div>

                    <div class="dns-selected-preview"
                        style="display:none; margin-bottom:15px; padding:10px; background:#f7f7f7; border:1px solid #ddd;">
                    </div>

                    <div class="dns-checkbox-list">

                        <?php
                        $serial = 1;
                        foreach ( $items as $item ) :
                            $is_checked = in_array( $item->term_id, $saved_items, true );
                            ?>

                            <label class="dns-checkbox <?php echo $is_checked ? 'dns-checked' : ''; ?>">
                                <input type="checkbox"
                                    name="<?php echo esc_attr( $name_attr ); ?>[]"
                                    value="<?php echo esc_attr( $item->term_id ); ?>"
                                    <?php checked( $is_checked ); ?>
                                >

                                <?php printf(
                                    esc_html__( '%d. %s', 'directorist-notification-system' ),
                                    $serial,
                                    esc_html( $item->name )
                                ); ?>
                            </label>

                            <?php
                            $serial++;
                        endforeach;
                        ?>

                    </div>

                <?php endif; ?>

            <?php
            }
            ?>

            <!-- ============================= -->
            <!-- JOB TAB -->
            <!-- ============================= -->
            <div class="dns-tab-content" id="tab-job">
                <?php
                dns_render_checkbox_block(
                    $job_types,
                    $saved['listing_types'] ?? array(),
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
                    $saved['listing_locations'] ?? array(),
                    __( 'No locations available.', 'directorist-notification-system' ),
                    'listing_locations'
                );
                ?>
            </div>

            <!-- ============================= -->
            <!-- Form Actions -->
            <!-- ============================= -->
            <div class="dns-actions">
                <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">
                    <?php esc_html_e( 'Confirm', 'directorist-notification-system' ); ?>
                </button>
            </div>

        </form>
    </div>
</div>
