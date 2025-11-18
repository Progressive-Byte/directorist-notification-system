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

        // Require login
        if ( ! is_user_logged_in() ) {
            return '<div class="dns-card"><p>' . esc_html__( 'You must be logged in to save preferences.', 'dns' ) . '</p></div>';
        }

        $user_id = get_current_user_id();
        $msg     = '';

        // --- HANDLE UNSUBSCRIBE ---
        if ( isset( $_POST['np_unsubscribe'] ) ) {
            dns_remove_user_from_subscriptions( $user_id );
            echo '<script>window.location.href="' . esc_url( get_permalink() ) . '"</script>';
            exit;
        }

        // --- LOAD TAXONOMY TERMS ---
        $listing_types = get_terms( [
            'taxonomy'   => 'atbdp_listing_types',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );

        $locations = get_terms( [
            'taxonomy'   => 'at_biz_dir-location',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );

        // --- LOAD SAVED USER PREFS ---
        $saved = get_user_meta( $user_id, 'dns_notify_prefs', true );
        $saved = is_array( $saved ) ? $saved : [];
        $saved = array_merge( [
            'listing_types'     => [],
            'listing_locations' => [],
        ], $saved );

        // --- HANDLE SAVE FORM ---
        if ( isset( $_POST['np_save'] ) && check_admin_referer( 'np_save_prefs', 'np_nonce' ) ) {

            $selected_types     = isset( $_POST['listing_types'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['listing_types'] ) ) : [];
            $selected_locations = isset( $_POST['listing_locations'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['listing_locations'] ) ) : [];

            // Save preferences
            $saved = [
                'listing_types'     => $selected_types,
                'listing_locations' => $selected_locations,
            ];
            update_user_meta( $user_id, 'dns_notify_prefs', $saved );

            // --- SYNC TERM META ---
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
        }

        // --- LOAD TEMPLATE ---
        $template = DNS_PLUGIN_TEMPLATE . 'Front/notify-preferences.php';

        return dns_load_template( $template, [
            'listing_types' => $listing_types,
            'locations'     => $locations,
            'saved'         => $saved,
            'msg'           => $msg,
        ], false );
    }






}
