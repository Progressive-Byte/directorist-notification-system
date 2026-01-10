<?php
use DNS\Helper\Messages;

/** @var array $data */
$data = is_array($data ?? null) ? $data : [];

/**
 * Extract saved data safely
 */
$listing_types     = $data['listing_types'] ?? [];
$listing_locations = $data['listing_locations'] ?? [];
?>

<div class="dns-wrap">
    <div class="dns-card">

        <h3 class="dns-title">
            <?php esc_html_e('Notification Preferences', 'directorist-notification-system'); ?>
        </h3>

        <p class="dns-sub">
            <?php esc_html_e(
                'Choose which listing types and locations you want updates for.',
                'directorist-notification-system'
            ); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field('np_save_prefs', 'np_nonce'); ?>

            <?php
            $selected_job_term = (int) get_option('dns_job_terms', '');

            $job_types_list = !empty($selected_job_term)
                ? dns_get_term_objects_by_directory($selected_job_term)
                : [];

            // -------------------------
            // Checkbox renderer
            // -------------------------
            function dns_render_checkbox_block($items, $saved_items, $empty_message, $name_attr, $show_controls = true) {
                if (empty($items)) {
                    echo '<p>' . esc_html($empty_message) . '</p>';
                    return;
                }
                ?>
                <div class="dns-checkbox-wrapper">
                    <?php if ($show_controls) : ?>
                    <div class="dns-search-wrapper">
                        <div style="position:relative; flex:1;">
                            <input type="text"
                                class="dns-search-input"
                                placeholder="<?php esc_attr_e('Search...', 'directorist-notification-system'); ?>"
                                autocomplete="off"
                            >
                            <span class="dns-search-clear" style="display:none;">×</span>
                        </div>

                        <button type="button" class="dns-btn dns-btn--mini dns-select-all">
                            <?php esc_html_e('Select All', 'directorist-notification-system'); ?>
                        </button>

                        <button type="button" class="dns-btn dns-btn--mini dns-deselect-all">
                            <?php esc_html_e('Deselect All', 'directorist-notification-system'); ?>
                        </button>

                        <button type="button" class="dns-btn dns-btn--mini dns-show-selected">
                            <span class="dns-show-selected-icon">👁️</span>
                            <span class="dns-show-selected-text">
                                <?php esc_html_e('Show Selected', 'directorist-notification-system'); ?>
                            </span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="dns-checkbox-list">
                        <?php
                        $i = 1;
                        foreach ($items as $item) :
                            $checked = in_array($item->term_id, $saved_items, true);
                            ?>
                            <label class="dns-checkbox <?php echo $checked ? 'dns-checked' : ''; ?>">
                                <input type="checkbox"
                                    name="<?php echo esc_attr($name_attr); ?>[]"
                                    value="<?php echo esc_attr($item->term_id); ?>"
                                    <?php checked($checked); ?>
                                >
                                <?php echo esc_html($i . '. ' . $item->name); ?>
                            </label>
                            <?php
                            $i++;
                        endforeach;
                        ?>
                    </div>
                </div>
                <?php
            }
            ?>

            <!-- Tabs Navigation -->
            <div class="dns-tabs">
                <?php if (!empty($job_types_list)) : ?>
                    <button type="button" class="dns-tab" data-tab="job">
                        <?php esc_html_e('Job Listing', 'directorist-notification-system'); ?>
                    </button>
                <?php endif; ?>

                <button type="button" class="dns-tab" data-tab="locations">
                    <?php esc_html_e('Location', 'directorist-notification-system'); ?>
                </button>
            </div>

            <!-- JOB TAB -->
            <?php if (!empty($job_types_list)) : ?>
                <div class="dns-tab-content" id="tab-job">
                    <?php
                    dns_render_checkbox_block(
                        $job_types_list,
                        $listing_types,
                        __('No job listings available.', 'directorist-notification-system'),
                        'listing_types'
                    );
                    ?>
                </div>
            <?php endif; ?>

            <!-- LOCATION TAB -->
            <div class="dns-tab-content" id="tab-locations">

                <?php
                $all_locations = get_terms([
                    'taxonomy'   => 'at_biz_dir-location',
                    'hide_empty' => false,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ]);

                if (is_wp_error($all_locations)) {
                    echo '<p>' . esc_html__('Error loading locations.', 'directorist-notification-system') . '</p>';
                } else {
                    $parents = [];
                    $children = [];
                    $orphans = [];

                    foreach ($all_locations as $loc) {
                        if ($loc->parent) {
                            $children[$loc->parent][] = $loc;
                        } else {
                            $parents[] = $loc;
                        }
                    }

                    // Separate parents with children from orphans
                    foreach ($parents as $key => $parent) {
                        if (empty($children[$parent->term_id])) {
                            $orphans[] = $parent;
                            unset($parents[$key]);
                        }
                    }
                    ?>

                    <!-- Single search/control bar for entire location tab -->
                    <?php if (!empty($parents) || !empty($orphans)) : ?>
                    <div class="dns-search-wrapper">
                        <div style="position:relative; flex:1;">
                            <input type="text"
                                class="dns-search-input"
                                placeholder="<?php esc_attr_e('Search...', 'directorist-notification-system'); ?>"
                                autocomplete="off"
                            >
                            <span class="dns-search-clear" style="display:none;">×</span>
                        </div>

                        <button type="button" class="dns-btn dns-btn--mini dns-select-all">
                            <?php esc_html_e('Select All', 'directorist-notification-system'); ?>
                        </button>

                        <button type="button" class="dns-btn dns-btn--mini dns-deselect-all">
                            <?php esc_html_e('Deselect All', 'directorist-notification-system'); ?>
                        </button>

                        <button type="button" class="dns-btn dns-btn--mini dns-show-selected">
                            <span class="dns-show-selected-icon">👁️</span>
                            <span class="dns-show-selected-text">
                                <?php esc_html_e('Show Selected', 'directorist-notification-system'); ?>
                            </span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Parent locations with children -->
                    <?php if (!empty($parents)) : ?>
                        <div class="dns-location-parents">
                            <?php foreach ($parents as $parent) : 
                                $selected_count = 0;
                                if (isset($children[$parent->term_id])) {
                                    foreach ($children[$parent->term_id] as $child) {
                                        if (in_array($child->term_id, $listing_locations, true)) {
                                            $selected_count++;
                                        }
                                    }
                                }
                                ?>
                                <div class="dns-location-parent <?php echo $selected_count > 0 ? 'dns-parent-has-selected' : ''; ?>" 
                                     data-parent-id="<?php echo esc_attr($parent->term_id); ?>">
                                    ▸ <?php echo esc_html($parent->name); ?>
                                    <?php if ($selected_count > 0) : ?>
                                        <strong>(<?php echo $selected_count; ?>)</strong>
                                    <?php endif; ?>
                                </div>

                                <div class="dns-location-children" 
                                     id="dns-children-<?php echo esc_attr($parent->term_id); ?>" 
                                     style="display:none;">
                                    <?php
                                    if (isset($children[$parent->term_id])) {
                                        dns_render_checkbox_block(
                                            $children[$parent->term_id],
                                            $listing_locations,
                                            __('No locations available.', 'directorist-notification-system'),
                                            'listing_locations',
                                            false  // Don't show controls for nested items
                                        );
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Orphan locations (locations without children) -->
                    <?php if (!empty($orphans)) : ?>
                        <?php
                        dns_render_checkbox_block(
                            $orphans,
                            $listing_locations,
                            __('No locations available.', 'directorist-notification-system'),
                            'listing_locations',
                            false  // Don't show controls since we have them at the top
                        );
                        ?>
                    <?php endif; ?>

                    <?php if (empty($parents) && empty($orphans)) : ?>
                        <p><?php esc_html_e('No locations available.', 'directorist-notification-system'); ?></p>
                    <?php endif; ?>

                <?php } ?>

            </div>

            <!-- FORM ACTIONS -->
            <div class="dns-actions">
                <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">
                    <?php esc_html_e('Confirm', 'directorist-notification-system'); ?>
                </button>
            </div>

        </form>
    </div>
</div>