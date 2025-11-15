<?php
namespace DNS\Frontend;

use DNS\Helper\Messages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Shortcode
 *
 * Handles the notification preferences shortcode.
 */
class Shortcode {

    /**
     * Shortcode constructor.
     */
    public function __construct() {
        add_shortcode( 'notification_system', [ $this, 'render' ] );
        add_action( 'wp_head', [ $this, 'head' ] );
    }

    /**
     * Debug or print user preferences in head (optional).
     *
     * @return void
     */
    public function head() {
        $post_id = 672;

        // $post_data = dns_get_post_data( $post_id );

        $taxonomies = [ 'atbdp_listing_types', 'at_biz_dir-location' ];
        $terms_with_users = dns_get_terms_with_subscribers( $taxonomies );

        // Messages::pri( $terms_with_users );
    }

    /**
     * Render the shortcode output.
     *
     * @return string
     */
    public function render() {

        // Require login.
        if ( ! is_user_logged_in() ) {
            return '<div class="dns-card"><p>' . esc_html__( 'You must be logged in to save preferences.', 'dns' ) . '</p></div>';
        }

        $user_id = get_current_user_id();

        // Fetch Taxonomy Terms.
        $listing_types = get_terms(
            [
                'taxonomy'   => 'atbdp_listing_types',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]
        );

        $locations = get_terms(
            [
                'taxonomy'   => 'at_biz_dir-location',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]
        );

        // Fetch All Listings.
        $listings = get_posts(
            [
                'post_type'      => 'at_biz_dir',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );

        // Load Saved User Preferences.
        $saved = get_user_meta( $user_id, 'dns_notify_prefs', true );
        $saved = is_array( $saved ) ? $saved : [];
        $saved = array_merge(
            [
                'listing_types'     => [],
                'listing_locations' => [],
                'listing_posts'     => [],
            ],
            $saved
        );

        $msg = '';

        if ( ! empty( $_POST['np_save'] ) && check_admin_referer( 'np_save_prefs', 'np_nonce' ) ) {

            $selected_types     = isset( $_POST['listing_types'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['listing_types'] ) ) : [];
            $selected_locations = isset( $_POST['listing_locations'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['listing_locations'] ) ) : [];
            $selected_listings  = isset( $_POST['listing_posts'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['listing_posts'] ) ) : [];

            // Save preferences in user meta.
            $saved = [
                'listing_types'     => $selected_types,
                'listing_locations' => $selected_locations,
                'listing_posts'     => $selected_listings,
            ];

            update_user_meta( $user_id, 'dns_notify_prefs', $saved );

            // Sync User Subscriptions with Term Meta.
            $taxonomies = [
                'atbdp_listing_types' => $listing_types,
                'at_biz_dir-location' => $locations,
            ];

            foreach ( $taxonomies as $taxonomy => $terms ) {

                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

                    $selected = ( 'atbdp_listing_types' === $taxonomy ) ? $selected_types : $selected_locations;

                    foreach ( $terms as $term ) {

                        $meta_key = 'subscribed_users';
                        $users    = get_term_meta( $term->term_id, $meta_key, true );
                        $users    = is_array( $users ) ? $users : [];

                        if ( in_array( $term->term_id, $selected, true ) ) {
                            if ( ! in_array( $user_id, $users, true ) ) {
                                $users[] = $user_id;
                            }
                        } else {
                            $users = array_diff( $users, [ $user_id ] );
                        }

                        update_term_meta( $term->term_id, $meta_key, $users );
                    }
                }
            }

            // Sync User Subscriptions with Post Meta.
            if ( ! empty( $listings ) ) {
                foreach ( $listings as $item ) {
                    $listing_id = $item->ID;
                    $meta_key   = 'subscribed_users';
                    $users      = get_post_meta( $listing_id, $meta_key, true );
                    $users      = is_array( $users ) ? $users : [];

                    if ( in_array( $listing_id, $selected_listings, true ) ) {
                        // Add user if selected
                        if ( ! in_array( $user_id, $users, true ) ) {
                            $users[] = $user_id;
                        }
                    } else {
                        // Remove user if not selected
                        $users = array_diff( $users, [ $user_id ] );
                    }

                    update_post_meta( $listing_id, $meta_key, $users );
                }
            }

        }

        // Output HTML.
        ob_start();
        ?>
        <div class="dns-wrap">
            <div class="dns-card">

                <h3 class="dns-title"><?php esc_html_e( 'Notification Preferences', 'dns' ); ?></h3>
                <p class="dns-sub"><?php esc_html_e( 'Choose which listing types or listings and locations you want updates for.', 'dns' ); ?></p>

                <?php echo wp_kses_post( $msg ); ?>

                <form method="post">
                    <?php wp_nonce_field( 'np_save_prefs', 'np_nonce' ); ?>

                    <!-- Tabs -->
                    <div class="dns-tabs">
                        <button type="button" class="dns-tab" data-tab="types"><?php esc_html_e( 'Listing Types', 'dns' ); ?></button>
                        <button type="button" class="dns-tab" data-tab="locations"><?php esc_html_e( 'Locations', 'dns' ); ?></button>
                        <button type="button" class="dns-tab" data-tab="listings"><?php esc_html_e( 'Listings', 'dns' ); ?></button>
                    </div>

                    <!-- Tab: Types -->
                    <div class="dns-tab-content" id="tab-types">
                        <?php if ( ! empty( $listing_types ) && ! is_wp_error( $listing_types ) ) : ?>
                            <?php foreach ( $listing_types as $type ) : ?>
                                <label class="dns-checkbox">
                                    <input
                                        type="checkbox"
                                        name="listing_types[]"
                                        value="<?php echo esc_attr( $type->term_id ); ?>"
                                        <?php checked( in_array( $type->term_id, $saved['listing_types'], true ) ); ?>
                                    >
                                    <?php echo esc_html( $type->name ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p><?php esc_html_e( 'No listing types found.', 'dns' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Tab: Locations -->
                    <div class="dns-tab-content" id="tab-locations">
                        <?php if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) : ?>
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
                                            <?php checked( in_array( $loc->term_id, $saved['listing_locations'], true ) ); ?>
                                        >
                                        <?php echo esc_html( $index + 1 . '. ' . $loc->name ); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p><?php esc_html_e( 'No locations found.', 'dns' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Tab: Listings -->
                    <div class="dns-tab-content" id="tab-listings">
                        <?php if ( ! empty( $listings ) ) : ?>
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
                                            <?php checked( in_array( $item->ID, $saved['listing_posts'], true ) ); ?>
                                        >
                                        <?php echo esc_html( $index + 1 . '. ' . $item->post_title ); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p><?php esc_html_e( 'No listings found.', 'dns' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Submit -->
                    <div class="dns-actions">
                        <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">
                            <?php esc_html_e( 'Save Preferences', 'dns' ); ?>
                        </button>
                    </div>

                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}
