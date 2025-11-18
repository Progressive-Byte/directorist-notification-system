<?php
namespace DNS\Common;
use DNS\Helper\Messages;

if ( ! defined( 'ABSPATH' ) ) exit;

class Common {

    public function __construct() {

        // Hook into post save
        add_action( 'save_post_at_biz_dir', [ $this, 'save_at_biz_dir' ], 999, 3 );

        // Background email processing
        add_action( 'dns_process_email_queue', [ $this, 'process_email_queue' ] );

        // Unsubscribe link handler
        add_action( 'template_redirect', [ $this, 'check_unsubscribe' ] );
        add_filter( 'bp_notifications_get_notifications_for_user', [ $this, 'send_notifications_for_user', 10, 3 ] );


        // add_action( 'wp_head', [$this, 'head' ] );

        
    }

    function head(){
         

           
    }

    /**
     * Fires when a post is created or updated.
     * Gathers all subscribed users and sends notifications.
     */
    public function save_at_biz_dir( $post_id, $post, $update ) {

        // Ignore autosaves and revisions
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Only target specific post types
        if ( 'at_biz_dir' !== $post->post_type ) {
            return;
        }

        // Only published posts
        if ( 'publish' !== $post->post_status ) {
            return;
        }

        // --- Get selected Job/Market terms from admin options ---
        $selected_market_term = get_option( 'dns_market_terms' );
        $selected_job_term    = get_option( 'dns_job_terms' );

        // --- Get post's directory type terms ---
        $post_terms = wp_get_post_terms( $post_id, ATBDP_DIRECTORY_TYPE, [ 'fields' => 'ids' ] );

        // --- Check if post belongs to selected Job or Market term ---
        $intersect = array_intersect( $post_terms, array_filter( [ $selected_market_term, $selected_job_term ] ) );

        if ( empty( $intersect ) ) {
            // Post does not belong to selected terms, skip
            return;
        }

        // --- Proceed with notifications ---
        $user_ids = [];

        // Get subscribers from post meta / term meta
        $data = dns_get_post_data( $post_id );

        if ( ! empty( $data['subscribed_users'] ) ) {
            $user_ids = array_merge( $user_ids, $data['subscribed_users'] );
        }

        if ( ! empty( $data['terms'] ) ) {
            foreach ( $data['terms'] as $taxonomy => $terms ) {
                foreach ( $terms as $term_id => $term_data ) {
                    if ( ! empty( $term_data['subscribed_users'] ) ) {
                        $user_ids = array_merge( $user_ids, $term_data['subscribed_users'] );
                    }
                }
            }
        }

        $user_ids = array_unique( $user_ids );

        if ( empty( $user_ids ) ) {
            // Fallback: get subscribers from taxonomies
            $taxonomies    = [ 'atbdp_listing_types', 'at_biz_dir-location' ];
            $taxonomy_data = dns_get_terms_with_subscribers( $taxonomies );
            $user_ids      = dns_extract_user_ids_from_taxonomy_data( $taxonomy_data );
        }

        if ( empty( $user_ids ) ) return;

        // --- Send notifications to all subscribers ---
        foreach ( $user_ids as $user_id ) {
            dns_send_listing_notification( $user_id, $post_id );
        }

        // Optional: queue emails for subscribers
        $this->queue_subscription_emails( $post_id, $user_ids );
    }





    /**
     * Queue subscription emails using transient + WP Cron
     */
    private function queue_subscription_emails( $post_id, $user_ids ) {

        $queue = get_transient( 'dns_email_queue' );
        if ( ! is_array( $queue ) ) $queue = [];

        $listing_title = get_the_title( $post_id );
        $listing_link  = get_permalink( $post_id );

        // Get listing type and city
        $listing_types = wp_get_post_terms( $post_id, 'atbdp_listing_types', ['fields' => 'names'] );
        $listing_cities = wp_get_post_terms( $post_id, 'at_biz_dir-location', ['fields' => 'names'] );

        foreach ( $user_ids as $user_id ) {
            $user_info = get_userdata( $user_id );
            if ( ! $user_info || empty( $user_info->user_email ) ) continue;

            $unsubscribe_url = add_query_arg(
                [
                    'dns_unsubscribe' => 1,
                    'user_id'        => $user_id,
                    'nonce'          => wp_create_nonce( 'dns_unsubscribe_' . $user_id ),
                ],
                site_url()
            );

            $message  = '<p>Hello ' . esc_html( $user_info->display_name ) . ',</p>';
            $message .= '<p>A new post has been published that matches your subscription preferences:</p>';
            $message .= '<ul>';
            $message .= '<li><strong>Title:</strong> <a href="' . esc_url( $listing_link ) . '">' . esc_html( $listing_title ) . '</a></li>';

            if ( ! empty( $listing_types ) ) {
                $message .= '<li><strong>Type:</strong> ' . esc_html( implode( ', ', $listing_types ) ) . '</li>';
            }

            if ( ! empty( $listing_cities ) ) {
                $message .= '<li><strong>City:</strong> ' . esc_html( implode( ', ', $listing_cities ) ) . '</li>';
            }

            $message .= '<li><strong>Link:</strong> <a href="' . esc_url( $listing_link ) . '">View Listing</a></li>';
            $message .= '</ul>';

            $message .= '<p>If you wish to unsubscribe from all notifications, click the button below:</p>';
            $message .= '<p><a href="' . esc_url( $unsubscribe_url ) . '" style="display:inline-block;padding:10px 20px;color:#ffffff;background-color:#0073aa;text-decoration:none;border-radius:5px;">Unsubscribe</a></p>';
            $message .= '<p>Thank you!</p>';

            $queue[] = [
                'to'      => $user_info->user_email,
                'subject' => 'New Listing Match Found: ' . $listing_title,
                'message' => $message,
                'headers' => ['Content-Type: text/html; charset=UTF-8'],
            ];
        }

        set_transient( 'dns_email_queue', $queue, HOUR_IN_SECONDS );

        if ( ! wp_next_scheduled( 'dns_process_email_queue' ) ) {
            wp_schedule_single_event( time() + 30, 'dns_process_email_queue' );
        }
    }



    /**
     * Process queued emails in the background
     */
    public function process_email_queue() {
        $queue = get_transient( 'dns_email_queue' );
        if ( empty( $queue ) || ! is_array( $queue ) ) {
            return;
        }

        foreach ( $queue as $key => $email_data ) {
            wp_mail(
                $email_data['to'],
                $email_data['subject'],
                $email_data['message'],
                isset( $email_data['headers'] ) ? $email_data['headers'] : ['Content-Type: text/html; charset=UTF-8']
            );

            // Remove sent email from queue
            unset( $queue[$key] );
        }

        // Save updated queue
        set_transient( 'dns_email_queue', $queue, HOUR_IN_SECONDS );
    }


    /**
     * Handle global unsubscribe requests
     */
        
    public function check_unsubscribe() {

        if ( ! isset( $_GET['dns_unsubscribe'], $_GET['user_id'], $_GET['nonce'] ) ) {
            return;
        }

        $user_id = absint( $_GET['user_id'] );
        $nonce   = sanitize_text_field( $_GET['nonce'] );

        if ( ! wp_verify_nonce( $nonce, 'dns_unsubscribe_' . $user_id ) ) {
            wp_die( 'Invalid request.' );
        }

        // Remove user from all subscriptions
        dns_remove_user_from_subscriptions( $user_id );

        // Optional: redirect with confirmation
        wp_redirect( home_url(  ) );
        exit;
    }

    public function send_notifications_for_user( $notifications, $user_id, $format ){
          foreach ( $notifications as &$n ) {

            // Only our notification
            if ( $n->component_action !== 'new_listing_match' ) {
                continue;
            }

            $msg  = bp_notifications_get_meta( $n->id, 'message', true );
            $link = bp_notifications_get_meta( $n->id, 'link', true );

            if ( $format === 'string' ) {
                $n->content = '<a href="' . esc_url( $link ) . '">' . esc_html( $msg ) . '</a>';
            }
        }

        return $notifications;  
    }


}
