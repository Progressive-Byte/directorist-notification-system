<div class="dns-wrap">
    <div class="dns-card">
        <h3 class="dns-title"><?php esc_html_e('Notification Preferences', 'directorist-notification-system'); ?></h3>
        <p class="dns-sub"><?php esc_html_e('Choose which listing types or listings and locations you want updates for.', 'directorist-notification-system'); ?></p>

        <form method="post">
            <?php wp_nonce_field('np_save_prefs', 'np_nonce'); ?>

            <?php
            $selected_market_term = (int) get_option('dns_market_terms', '');
            $selected_job_term    = get_option('dns_job_terms', '');
            ?>

            <!-- Tabs Navigation -->
            <div class="dns-tabs">
                <button type="button" class="dns-tab" data-tab="market"><?php esc_html_e('Market Place Listing', 'directorist-notification-system'); ?></button>
                <button type="button" class="dns-tab" data-tab="job"><?php esc_html_e('Job Listing', 'directorist-notification-system'); ?></button>
                <button type="button" class="dns-tab" data-tab="locations"><?php esc_html_e('Location', 'directorist-notification-system'); ?></button>
            </div>

            <!-- MARKET TAB -->
            <div class="dns-tab-content" id="tab-market">
                <?php
                $market_types = !empty($selected_market_term) ? get_all_terms_by_directory_type($selected_market_term) : [];
                ?>                

                <?php if (empty($market_types)) : ?>
                    <p><?php esc_html_e('Please select Market listing.', 'directorist-notification-system'); ?></p>
                <?php else : ?>
                    <div class="dns-search-wrapper" style="display:flex; gap:10px; margin-bottom:10px;">
                        <input type="text" class="dns-search-input" placeholder="<?php esc_attr_e('Search...', 'directorist-notification-system'); ?>" style="flex:1;">
                        <button type="button" class="dns-btn dns-btn--mini dns-select-all"><?php esc_html_e('Select All', 'directorist-notification-system'); ?></button>
                        <button type="button" class="dns-btn dns-btn--mini dns-deselect-all"><?php esc_html_e('Deselect All', 'directorist-notification-system'); ?></button>
                        <button type="button" class="dns-btn dns-btn--mini dns-show-selected">
                            <span class="dns-show-selected-icon">👁️</span>
                            <span class="dns-show-selected-text"><?php esc_html_e('Show Selected', 'directorist-notification-system'); ?></span>
                        </button>
                    </div>

                    <div class="dns-selected-preview" style="display:none; margin-bottom:15px; padding:10px; background:#f7f7f7; border:1px solid #ddd;"></div>
                    <div class="dns-checkbox-list">
                        <?php $serial = 1;
                        foreach ($market_types as $type) :
                            $is_checked = in_array($type->term_id, $saved['market_types'] ?? [], true);
                        ?>
                            <label class="dns-checkbox <?php echo $is_checked ? 'dns-checked' : ''; ?>">
                                <input type="checkbox" name="market_types[]" value="<?php echo esc_attr($type->term_id); ?>" <?php checked($is_checked); ?>>
                                <?php printf(esc_html__('%d. %s', 'directorist-notification-system'), $serial, $type->name); ?>
                            </label>
                        <?php $serial++; endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- JOB TAB -->
            <div class="dns-tab-content" id="tab-job">
                <?php
                $job_types_list = !empty($selected_job_term) ? get_all_terms_by_directory_type($selected_job_term) : [];
                ?>                

                <?php if (empty($job_types_list)) : ?>
                    <p><?php esc_html_e('Please select Job listing.', 'directorist-notification-system'); ?></p>
                <?php else : ?>
                    <div class="dns-search-wrapper" style="display:flex; gap:10px; margin-bottom:10px;">
                        <input type="text" class="dns-search-input" placeholder="<?php esc_attr_e('Search...', 'directorist-notification-system'); ?>" style="flex:1;">
                        <button type="button" class="dns-btn dns-btn--mini dns-select-all"><?php esc_html_e('Select All', 'directorist-notification-system'); ?></button>
                        <button type="button" class="dns-btn dns-btn--mini dns-deselect-all"><?php esc_html_e('Deselect All', 'directorist-notification-system'); ?></button>
                        <button type="button" class="dns-btn dns-btn--mini dns-show-selected">
                            <span class="dns-show-selected-icon">👁️</span>
                            <span class="dns-show-selected-text"><?php esc_html_e('Show Selected', 'directorist-notification-system'); ?></span>
                        </button>
                    </div>

                    <div class="dns-selected-preview" style="display:none; margin-bottom:15px; padding:10px; background:#f7f7f7; border:1px solid #ddd;"></div>
                    <div class="dns-checkbox-list">
                        <?php $serial = 1;
                        foreach ($job_types_list as $type) :
                            $is_checked = in_array($type->term_id, $saved['listing_types'] ?? [], true);
                        ?>
                            <label class="dns-checkbox <?php echo $is_checked ? 'dns-checked' : ''; ?>">
                                <input type="checkbox" name="listing_types[]" value="<?php echo esc_attr($type->term_id); ?>" <?php checked($is_checked); ?>>
                                <?php printf(esc_html__('%d. %s', 'directorist-notification-system'), $serial, $type->name); ?>
                            </label>
                        <?php $serial++; endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- LOCATION TAB -->
            <div class="dns-tab-content" id="tab-locations">
                <?php if (empty($locations)) : ?>
                    <p><?php esc_html_e('No locations available.', 'directorist-notification-system'); ?></p>
                <?php else : ?>
                    <div class="dns-search-wrapper" style="display:flex; gap:10px; margin-bottom:10px;">
                        <input type="text" class="dns-search-input" placeholder="<?php esc_attr_e('Search...', 'directorist-notification-system'); ?>" style="flex:1;">
                        <button type="button" class="dns-btn dns-btn--mini dns-select-all"><?php esc_html_e('Select All', 'directorist-notification-system'); ?></button>
                        <button type="button" class="dns-btn dns-btn--mini dns-deselect-all"><?php esc_html_e('Deselect All', 'directorist-notification-system'); ?></button>
                        <button type="button" class="dns-btn dns-btn--mini dns-show-selected">
                            <span class="dns-show-selected-icon">👁️</span>
                            <span class="dns-show-selected-text"><?php esc_html_e('Show Selected', 'directorist-notification-system'); ?></span>
                        </button>
                    </div>

                    <div class="dns-selected-preview" style="display:none; margin-bottom:15px; padding:10px; background:#f7f7f7; border:1px solid #ddd;"></div>
                    <div class="dns-checkbox-list">
                        <?php foreach ($locations as $index => $loc) :
                            $is_checked = in_array($loc->term_id, $saved['listing_locations'] ?? [], true);
                        ?>
                            <label class="dns-checkbox <?php echo $is_checked ? 'dns-checked' : ''; ?>">
                                <input type="checkbox" name="listing_locations[]" value="<?php echo esc_attr($loc->term_id); ?>" <?php checked($is_checked); ?>>
                                <?php printf(esc_html__('%d. %s', 'directorist-notification-system'), $index + 1, $loc->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Form Actions -->
            <div class="dns-actions">
                <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1"><?php esc_html_e('Confirm', 'directorist-notification-system'); ?></button>
            </div>
        </form>
    </div>
</div>
