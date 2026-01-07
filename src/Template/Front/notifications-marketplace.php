<?php
use DNS\Helper\Messages;

/** -----------------------------
 *  SAFETY: Normalize variables
 *  ----------------------------- */
$data      = is_array( $data ?? null ) ? $data : [];
$locations = is_array( $locations ?? null ) ? $locations : [];

/** -----------------------------
 *  Extract saved marketplace data
 *  ----------------------------- */
$market_listings  = $data['market_types']['listing']   ?? [];
$market_locations = $data['market_types']['locations'] ?? [];
?>

<div class="dns-wrap">
    <div class="dns-card">

        <h3 class="dns-title">
            <?php esc_html_e( 'Notification Preferences', 'directorist-notification-system' ); ?>
        </h3>

        <p class="dns-sub">
            <?php esc_html_e(
                'Choose which marketplace listings and locations you want updates for.',
                'directorist-notification-system'
            ); ?>
        </p>

        <form method="post">

            <?php wp_nonce_field( 'np_save_prefs', 'np_nonce' ); ?>

            <?php
            $selected_market_term = (int) get_option( 'dns_market_terms', '' );

            $market_types = ! empty( $selected_market_term )
                ? dns_get_term_objects_by_directory( $selected_market_term )
                : [];
            ?>

            <!-- ============================= -->
            <!-- Tabs -->
            <!-- ============================= -->
            <div class="dns-tabs">
                <button type="button" class="dns-tab" data-tab="market">
                    <?php esc_html_e( 'Market Place Listing', 'directorist-notification-system' ); ?>
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
            <!-- MARKET TAB -->
            <!-- ============================= -->
            <div class="dns-tab-content" id="tab-market">
                <?php
                dns_render_checkbox_block(
                    $market_types,
                    $market_listings,
                    __( 'Please select Market listings.', 'directorist-notification-system' ),
                    'market_types'
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
                    $market_locations,
                    __( 'No locations available.', 'directorist-notification-system' ),
                    'listing_locations'
                );
                ?>
            </div>

            <!-- ============================= -->
            <!-- ACTIONS -->
            <!-- ============================= -->
            <div class="dns-actions">
                <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">
                    <?php esc_html_e( 'Confirm', 'directorist-notification-system' ); ?>
                </button>
            </div>

        </form>

        <?php
        // Debug if needed
        // Messages::pri( $data );
        ?>

    </div>
</div>
