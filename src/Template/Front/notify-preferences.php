<div class="dns-wrap">
    <div class="dns-card">
        <h3 class="dns-title"><?php esc_html_e('Notification Preferences', 'dns'); ?></h3>
        <p class="dns-sub"><?php esc_html_e('Choose which listing types or listings and locations you want updates for.', 'dns'); ?></p>

        <?php echo wp_kses_post($msg); ?>

        <form method="post">
            <?php wp_nonce_field('np_save_prefs', 'np_nonce'); ?>

            <!-- Tabs Navigation -->
            <div class="dns-tabs">
                <button type="button" class="dns-tab" data-tab="market">
                    <?php esc_html_e('Market Place Listing', 'dns'); ?>
                </button>
                <button type="button" class="dns-tab" data-tab="job">
                    <?php esc_html_e('Job Listing', 'dns'); ?>
                </button>
                <button type="button" class="dns-tab" data-tab="locations">
                    <?php esc_html_e('Location', 'dns'); ?>
                </button>
            </div>

            <!-- Market Place Listing Tab -->
            <div class="dns-tab-content" id="tab-market">
                <?php
                $selected_market_term = get_option('dns_market_terms', '');
                $market_types = array_filter($listing_types, fn($type) => $type->term_id == $selected_market_term);

                foreach ($market_types as $type) : ?>
                    <label class="dns-checkbox">
                        <input type="checkbox"
                               name="listing_types[]"
                               value="<?php echo esc_attr($type->term_id); ?>"
                               <?php checked(in_array($type->term_id, $saved['listing_types'], true)); ?>>
                        <?php echo esc_html($type->name); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Job Listing Tab -->
            <div class="dns-tab-content" id="tab-job">
                <?php
                $selected_job_term = get_option('dns_job_terms', '');
                $job_types = array_filter($listing_types, fn($type) => $type->term_id == $selected_job_term);

                foreach ($job_types as $type) : ?>
                    <label class="dns-checkbox">
                        <input type="checkbox"
                               name="listing_types[]"
                               value="<?php echo esc_attr($type->term_id); ?>"
                               <?php checked(in_array($type->term_id, $saved['listing_types'], true)); ?>>
                        <?php echo esc_html($type->name); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Location Tab -->
            <div class="dns-tab-content" id="tab-locations">
                <div class="dns-search-box">
                    <input type="text" id="dns-location-search" placeholder="<?php esc_attr_e('Search location...', 'dns'); ?>">
                </div>
                <div class="dns-location-list">
                    <?php foreach ($locations as $index => $loc) : ?>
                        <label class="dns-checkbox">
                            <input type="checkbox"
                                   name="listing_locations[]"
                                   value="<?php echo esc_attr($loc->term_id); ?>"
                                   <?php checked(in_array($loc->term_id, $saved['listing_locations'], true)); ?>>
                            <?php echo esc_html(($index + 1) . '. ' . $loc->name); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="dns-actions">
                <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">
                    <?php esc_html_e('Subscribe', 'dns'); ?>
                </button>
                <button class="dns-btn dns-btn--secondary" type="submit" name="np_unsubscribe" value="1">
                    <?php esc_html_e('Unsubscribe', 'dns'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
