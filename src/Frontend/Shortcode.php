<?php
namespace DNS\Frontend;

use DNS\Helper\Messages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcode {

    public function __construct() {
        add_shortcode( 'notification_job', [ $this, 'job' ] );
        add_shortcode( 'notification_marketplace', [ $this, 'marketplace' ] );
        add_action( 'wp_head', [ $this, 'head' ] );
    }

    /**
     * Optional debugging hook.
     */
    public function head() {}

    /**
     * Render Job Notification Shortcode
     *
     * @return string
     */
    public function job() {

        // Require login
        if ( ! is_user_logged_in() ) {
            return '<div class="dns-card"><p>' . esc_html__( 'You must be logged in to save preferences.', 'dns' ) . '</p></div>';
        }

        $user_id = get_current_user_id();

        // Get all listing locations
        $locations = get_terms( [
            'taxonomy'   => 'at_biz_dir-location',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );

        // Load saved preferences
        $saved = get_user_meta( $user_id, 'dns_notify_prefs', true );

        // --------------------------
        // HANDLE SAVE FORM
        // --------------------------
        if ( isset( $_POST['np_save'] ) && check_admin_referer( 'np_save_prefs', 'np_nonce' ) ) {

            $selected_jobs       = isset( $_POST['listing_types'] ) 
                ? array_map( 'intval', (array) wp_unslash( $_POST['listing_types'] ) ) 
                : [];

            $selected_marketplace = isset( $_POST['market_types'] ) 
                ? array_map( 'intval', (array) wp_unslash( $_POST['market_types'] ) ) 
                : [];

            $selected_locations   = isset( $_POST['listing_locations'] ) 
                ? array_map( 'intval', (array) wp_unslash( $_POST['listing_locations'] ) ) 
                : [];

            // Save user preferences
            $saved = [
                'listing_types'     => $selected_jobs,
                'market_types'      => $selected_marketplace,
                'listing_locations' => $selected_locations,
            ];

            update_user_meta( $user_id, 'dns_notify_prefs', $saved );

            // Update term meta for subscribed users
            dns_add_user_to_term( $selected_jobs, $user_id );
            dns_add_user_to_term( $selected_marketplace, $user_id );
            dns_add_user_to_term( $selected_locations, $user_id );
        }

        // Load Template
        $template = DNS_PLUGIN_TEMPLATE . 'Front/notifications-jobs.php';

        return dns_load_template( $template, [
            'locations' => $locations,
            'saved'     => $saved,
        ], false );
    }

}
