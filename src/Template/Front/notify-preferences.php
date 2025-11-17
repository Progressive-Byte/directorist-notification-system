<div class="dns-wrap">
    <div class="dns-card">
        <h3 class="dns-title"><?php esc_html_e( 'Notification Preferences', 'dns' ); ?></h3>
        <p class="dns-sub"><?php esc_html_e( 'Choose which listing types or listings and locations you want updates for.', 'dns' ); ?></p>

        <?php echo wp_kses_post( $msg ); ?>

        <form method="post">
            <?php wp_nonce_field( 'np_save_prefs', 'np_nonce' ); ?>

            <div class="dns-tabs">
                <button type="button" class="dns-tab" data-tab="types"><?php esc_html_e( 'Listing Types', 'dns' ); ?></button>
                <button type="button" class="dns-tab" data-tab="locations"><?php esc_html_e( 'Locations', 'dns' ); ?></button>
                <button type="button" class="dns-tab" data-tab="listings"><?php esc_html_e( 'Listings', 'dns' ); ?></button>
            </div>

            <div class="dns-tab-content" id="tab-types">
                <?php foreach ( $listing_types as $type ) : ?>
                    <label class="dns-checkbox">
                        <input type="checkbox"
                            name="listing_types[]"
                            value="<?php echo esc_attr( $type->term_id ); ?>"
                            <?php checked( in_array( $type->term_id, $saved['listing_types'], true ) ); ?>>
                        <?php echo esc_html( $type->name ); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="dns-tab-content" id="tab-locations">
                <div class="dns-search-box">
                    <input type="text" id="dns-location-search" placeholder="<?php esc_attr_e( 'Search location...', 'dns' ); ?>">
                </div>
                <div class="dns-location-list">
                    <?php foreach ( $locations as $index => $loc ) : ?>
                        <label class="dns-checkbox">
                            <input
                                type="checkbox"
                                name="listing_locations[]"
                                value="<?php echo esc_attr( $loc->term_id ); ?>"
                                <?php checked( in_array( $loc->term_id, $saved['listing_locations'], true ) ); ?>>
                            <?php echo esc_html( $index + 1 . '. ' . $loc->name ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dns-tab-content" id="tab-listings">
                <div class="dns-search-box">
                    <input type="text" id="dns-listing-search" placeholder="<?php esc_attr_e( 'Search listings...', 'dns' ); ?>">
                </div>
                <div class="dns-listing-list">
                    <?php foreach ( $listings as $index => $item ) : ?>
                        <label class="dns-checkbox">
                            <input
                                type="checkbox"
                                name="listing_posts[]"
                                value="<?php echo esc_attr( $item->ID ); ?>"
                                <?php checked( in_array( $item->ID, $saved['listing_posts'], true ) ); ?>>
                            <?php echo esc_html( $index + 1 . '. ' . $item->post_title ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dns-actions">
                <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">
                    <?php esc_html_e( 'Subscribe', 'dns' ); ?>
                </button>
                <button class="dns-btn dns-btn--secondary" type="submit" name="np_unsubscribe" value="1">
                    <?php esc_html_e( 'Unsubscribe', 'dns' ); ?>
                </button>
            </div>
        </form>
    </div>
</div>
