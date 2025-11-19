<div class="dns-wrap">
    <div class="dns-card">
        <h3 class="dns-title"><?php esc_html_e( 'Notification Preferences', 'dns' ); ?></h3>
        <p class="dns-sub"><?php esc_html_e( 'Choose which listing types or listings and locations you want updates for.', 'dns' ); ?></p>

        <?php echo wp_kses_post( $msg ); ?>

        <form method="post">
            <?php wp_nonce_field( 'np_save_prefs', 'np_nonce' ); ?>

            <?php
            $selected_market_term = (int) get_option( 'dns_market_terms', '' );
            $selected_job_term    = get_option( 'dns_job_terms', '' );
            ?>

            <!-- Tabs Navigation (Always Visible) -->
            <div class="dns-tabs">
                <button type="button" class="dns-tab" data-tab="market">
                    <?php esc_html_e( 'Market Place Listing', 'dns' ); ?>
                </button>
                <button type="button" class="dns-tab" data-tab="job">
                    <?php esc_html_e( 'Job Listing', 'dns' ); ?>
                </button>
                <button type="button" class="dns-tab" data-tab="locations">
                    <?php esc_html_e( 'Location', 'dns' ); ?>
                </button>
            </div>

            <!-- Market Place Listing Tab -->
             <div class="dns-tab-content" id="tab-market">
                <?php
                $market_types = [];

                if ( ! empty( $selected_market_term ) ) {
                    $market_types = get_all_terms_by_directory_type( $selected_market_term );
                }

                if ( empty( $selected_market_term ) || empty( $market_types ) ) :
                    ?>
                    <p><?php esc_html_e( 'Please select Market listing.', 'dns' ); ?></p>
                <?php else : ?>
                    <?php 
                    $serial = 1; // initialize serial number
                    foreach ( $market_types as $type ) : ?>
                        <label class="dns-checkbox">
                            <input
                                type="checkbox"
                                name="market_types[]"
                                value="<?php echo esc_attr( $type->term_id ); ?>"
                                <?php checked( in_array( $type->term_id, $saved['market_types'] ?? [], true ) ); ?>
                            >
                            <?php echo esc_html( $serial . '. ' . $type->name ); ?>
                        </label>
                    <?php 
                    $serial++; // increment serial
                    endforeach; ?>
                <?php endif; ?>
            </div>




            <!-- Job Listing Tab -->
            <div class="dns-tab-content" id="tab-job">
                <?php
                $job_types_list = [];

                if ( ! empty( $selected_job_term ) ) {
                    // Filter $listing_types to only include the selected job term
                    $job_types_list =  get_all_terms_by_directory_type( $selected_job_term );
                }

                if ( empty( $selected_job_term ) || empty( $job_types_list ) ) :
                    ?>
                    <p><?php esc_html_e( 'Please select job listing.', 'dns' ); ?></p>
                <?php else : ?>
                    <?php 
                    $serial = 1; // initialize serial number
                    foreach ( $job_types_list as $type ) : ?>
                        <label class="dns-checkbox">
                            <input
                                type="checkbox"
                                name="listing_types[]"
                                value="<?php echo esc_attr( $type->term_id ); ?>"
                                <?php checked( in_array( $type->term_id, $saved['listing_types'] ?? [], true ) ); ?>
                            >
                            <?php echo esc_html( $serial . '. ' . $type->name ); ?>
                        </label>                    <?php 
                    $serial++; // increment serial
                    endforeach; ?>
                <?php endif; ?>
            </div>


            <!-- Location Tab -->
            <div class="dns-tab-content" id="tab-locations">
                    <?php if ( empty( $locations ) ) : ?>
                        <p><?php esc_html_e( 'No locations available.', 'dns' ); ?></p>
                    <?php else : ?>
                        <!-- Search + Button Wrapper -->
                        <div class="dns-search-wrapper" style="display:flex; gap:10px; margin-bottom:10px;">
                            <input
                                type="text"
                                id="dns-location-search"
                                placeholder="<?php esc_attr_e( 'Search location...', 'dns' ); ?>"
                                style="flex:1;" 
                            >
                            <button 
                                type="button" 
                                id="dns-show-selected-locations" 
                                class="dns-btn dns-btn--mini"
                            >
                                <?php esc_html_e( 'Show Selected', 'dns' ); ?>
                            </button>

                            <button 
                                type="button" 
                                id="dns-select-all-locations" 
                                class="dns-btn dns-btn--mini"
                            >
                                <?php esc_html_e( 'Select All', 'dns' ); ?>
                            </button>

                            <button 
                                type="button" 
                                id="dns-de-select-all-locations" 
                                class="dns-btn dns-btn--mini"
                            >
                                <?php esc_html_e( 'Deselect All', 'dns' ); ?>
                            </button>
                        </div>

                        <!-- Selected Preview Box -->
                        <div 
                            id="dns-selected-preview" 
                            style="display:none; margin-bottom:15px; padding:10px; background:#f7f7f7; border:1px solid #ddd;"
                        ></div>

                        <!-- Locations List -->
                        <div class="dns-location-list">
                            <?php foreach ( $locations as $index => $loc ) : 
                                $is_checked = in_array( $loc->term_id, $saved['listing_locations'], true );
                            ?>
                                <label class="dns-checkbox <?php echo $is_checked ? 'dns-checked' : ''; ?>">
                                    <input
                                        type="checkbox"
                                        name="listing_locations[]"
                                        value="<?php echo esc_attr( $loc->term_id ); ?>"
                                        <?php checked( $is_checked ); ?>
                                    >
                                    <?php echo esc_html( ( $index + 1 ) . '. ' . $loc->name ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <!-- Form Actions -->
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

