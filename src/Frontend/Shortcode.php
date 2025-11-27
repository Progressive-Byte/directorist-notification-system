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
        return $this->render_shortcode( 'listing_types', 'Front/notifications-jobs.php' );
    }

    /**
     * Render Marketplace Notification Shortcode
     *
     * @return string
     */
    public function marketplace() {
        return $this->render_shortcode( 'market_types', 'Front/notifications-marketplace.php' );
    }

    /**
     * Common handler for rendering a shortcode
     *
     * @param string $type_key Key for the type of listings (job or marketplace)
     * @param string $template_file Template file path relative to plugin template dir
     * @return string
     */
    private function render_shortcode( $type_key, $template_file ) {

        // Require login
        if ( ! is_user_logged_in() ) {
            return '<div class="dns-card"><p>' . esc_html__( 'You must be logged in to save preferences.', 'dns' ) . '</p></div>';
        }

        $user_id = get_current_user_id();
        $message = '';
        $message_class = '';

        // Get all listing locations
        $locations = get_terms([
            'taxonomy'   => 'at_biz_dir-location',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        // load saved prefs
        $saved = get_user_meta( $user_id, 'dns_notify_prefs', true );

        // --------------------------------
        // HANDLE SAVE FORM
        // --------------------------------
        if ( isset($_POST['np_save']) && check_admin_referer('np_save_prefs', 'np_nonce') ) {

            $selected_types = isset($_POST[$type_key])
                ? array_map('intval', (array) wp_unslash($_POST[$type_key]))
                : [];

            $selected_locations = isset($_POST['listing_locations'])
                ? array_map('intval', (array) wp_unslash($_POST['listing_locations']))
                : [];

            // previous values
            $previous_types     = isset($saved[$type_key]) ? (array) $saved[$type_key] : [];
            $previous_locations = isset($saved['listing_locations']) ? (array) $saved['listing_locations'] : [];

            // Remove unchecked ones
            remove_user_from_terms( array_diff($previous_types, $selected_types), $user_id );
            remove_user_from_terms( array_diff($previous_locations, $selected_locations), $user_id );

            // Save new prefs
            $saved = [
                $type_key           => $selected_types,
                'listing_locations' => $selected_locations,
            ];
            update_user_meta( $user_id, 'dns_notify_prefs', $saved );

            // Add newly checked ones
            dns_add_user_to_term( $selected_types, $user_id );
            dns_add_user_to_term( $selected_locations, $user_id );

            // -----------------------------
            // SUCCESS MESSAGE
            // -----------------------------
            $message = __( 'Your notification preferences have been saved successfully!', 'dns' );
            $message_class = 'dns-success';
        }

        // Load template
        $template = DNS_PLUGIN_TEMPLATE . $template_file;

        $content = dns_load_template( $template, [
            'locations' => $locations,
            'saved'     => $saved,
        ], false );

        // Add message before template
        if ( $message ) {
            $alert = '<div class="dns-alert ' . esc_attr($message_class) . '">' . esc_html($message) . '</div>';

            // Add fade-out script
            $alert .= "
                <script>
                    setTimeout(function() {
                        var box = document.querySelector('.dns-alert');
                        if (box) {
                            box.style.opacity = '0';
                            box.style.transition = 'opacity 0.7s ease';
                        }
                    }, 2500);
                </script>
            ";

            return $alert . $content;
        }

        return $content;
    }
   

   
}
