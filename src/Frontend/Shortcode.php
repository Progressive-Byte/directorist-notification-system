<?php
namespace DNS\Frontend;
use DNS\Helper\Messages;

if (!defined('ABSPATH')) exit;


class Shortcode {

    public function __construct() {
        add_shortcode('notification_system', [$this, 'render']);
    }

    /**
     * Render shortcode
     */
    public function render() {

        if (!is_user_logged_in()) {
            return '<div class="dns-card"><p>You must be logged in to save preferences.</p></div>';
        }

        $user_id = get_current_user_id();

        /*----------------------------------------
        | Fetch Taxonomy Terms
        ----------------------------------------*/
        $listing_types = get_terms([
            'taxonomy'   => 'atbdp_listing_types',
            'hide_empty' => false,
            'orderby'    => 'date',
            'order'      => 'DESC',
        ]);

        $locations = get_terms([
            'taxonomy'   => 'at_biz_dir-location',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        /*----------------------------------------
        | Fetch All Listings (Always Needed)
        ----------------------------------------*/
        $listings = get_posts([
            'post_type'      => 'at_biz_dir',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        /*----------------------------------------
        | Load Saved User Preferences
        ----------------------------------------*/
        $saved = get_user_meta($user_id, 'dns_notify_prefs', true);
        $saved = is_array($saved) ? $saved : [];

        /*----------------------------------------
        | Handle Form Submission
        ----------------------------------------*/
        $msg = '';

        if (!empty($_POST['np_save']) && check_admin_referer('np_save_prefs', 'np_nonce')) {

            $selected_types     = isset($_POST['listing_types'])      ? array_map('intval', (array)$_POST['listing_types'])      : [];
            $selected_locations = isset($_POST['listing_locations'])  ? array_map('intval', (array)$_POST['listing_locations'])  : [];
            $selected_listings  = isset($_POST['listing_posts'])      ? array_map('intval', (array)$_POST['listing_posts'])      : [];

            // Save preferences in user meta
            $saved = [
                'listing_types'     => $selected_types,
                'listing_locations' => $selected_locations,
                'listing_posts'     => $selected_listings,
            ];

            update_user_meta($user_id, 'dns_notify_prefs', $saved);

            /*----------------------------------------
            | Sync User Subscriptions with Term Meta
            ----------------------------------------*/
            $taxonomies = [
                'atbdp_listing_types' => $listing_types,
                'at_biz_dir-location' => $locations,
            ];

            foreach ($taxonomies as $taxonomy => $terms) {
                if (!empty($terms) && !is_wp_error($terms)) {

                    $selected = ($taxonomy === 'atbdp_listing_types')
                        ? $selected_types
                        : $selected_locations;

                    foreach ($terms as $term) {

                        $meta_key = 'subscribed_users';
                        $users = get_term_meta($term->term_id, $meta_key, true);
                        $users = is_array($users) ? $users : [];

                        // Add user to term
                        if (in_array($term->term_id, $selected)) {
                            if (!in_array($user_id, $users)) {
                                $users[] = $user_id;
                            }
                        } 
                        // Remove user from term
                        else {
                            $users = array_diff($users, [$user_id]);
                        }

                        update_term_meta($term->term_id, $meta_key, $users);
                    }
                }
            }
        }

        /*----------------------------------------
        | Output HTML
        ----------------------------------------*/
        ob_start(); ?>

        <div class="dns-wrap">
            <div class="dns-card">

                <h3 class="dns-title">Notification Preferences</h3>
                <p class="dns-sub">Choose which listing types and locations you want updates for.</p>
                <?= $msg; ?>

                <form method="post">
                    <?php wp_nonce_field('np_save_prefs','np_nonce'); ?>

                    <!-- Tabs -->
                    <div class="dns-tabs">
                        <button type="button" class="dns-tab " data-tab="types">Listing Types</button>
                        <button type="button" class="dns-tab" data-tab="locations">Locations</button>
                        <button type="button" class="dns-tab" data-tab="listings">Listings</button>
                    </div>

                    <!-- Tab: Types -->
                    <div class="dns-tab-content " id="tab-types">
                        <?php if (!empty($listing_types) && !is_wp_error($listing_types)): ?>
                            <?php foreach ($listing_types as $type): ?>
                                <label class="dns-checkbox">
                                    <input type="checkbox" 
                                           name="listing_types[]" 
                                           value="<?= esc_attr($type->term_id); ?>"
                                           <?= isset($saved['listing_types']) && in_array($type->term_id, $saved['listing_types']) ? 'checked' : ''; ?>>
                                    <?= esc_html($type->name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No listing types found.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Tab: Locations -->
                    <div class="dns-tab-content" id="tab-locations">

                        <?php if (!empty($locations) && !is_wp_error($locations)): ?>

                            <div class="dns-search-box">
                                <input type="text" id="dns-location-search" placeholder="Search location...">
                            </div>

                            <div class="dns-location-list">
                                <?php foreach ($locations as $loc): ?>
                                    <label class="dns-checkbox">
                                        <input type="checkbox" 
                                               name="listing_locations[]" 
                                               value="<?= esc_attr($loc->term_id); ?>"
                                               <?= isset($saved['listing_locations']) && in_array($loc->term_id, $saved['listing_locations']) ? 'checked' : ''; ?>>
                                        <?= esc_html($loc->name); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </div>

                        <?php else: ?>
                            <p>No locations found.</p>
                        <?php endif; ?>

                    </div>

                    <!-- Tab: Listings -->
                    <div class="dns-tab-content" id="tab-listings">

                        <?php if (!empty($listings)): ?>

                            <div class="dns-search-box">
                                <input type="text" id="dns-listing-search" placeholder="Search listings...">
                            </div>

                            <div class="dns-listing-list">
                                <?php foreach ($listings as $item): ?>
                                    <label class="dns-checkbox">
                                        <input type="checkbox" 
                                               name="listing_posts[]" 
                                               value="<?= esc_attr($item->ID); ?>"
                                               <?= isset($saved['listing_posts']) && in_array($item->ID, $saved['listing_posts']) ? 'checked' : ''; ?>>
                                        <?= esc_html($item->post_title); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </div>

                        <?php else: ?>
                            <p>No listings found.</p>
                        <?php endif; ?>

                    </div>

                    <!-- Submit -->
                    <div class="dns-actions">
                        <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">
                            Save Preferences
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

}