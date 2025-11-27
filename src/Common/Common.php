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


        add_action( 'wp_head', [$this, 'head' ] );

        
    }

    function head(){

        // Get all user preferences (listing_types, market_types, listing_locations)

        // $user_id = 1238;
        // $prefs = get_user_meta( $user_id, 'dns_notify_prefs', true );

        // if ( ! is_array( $prefs ) || empty( $prefs ) ) {
        //     return false;
        // }

        // // Loop through each group and remove user from subscribed term meta
        // foreach ( $prefs as $group_key => $term_ids ) {

        //     if ( empty( $term_ids ) || ! is_array( $term_ids ) ) {
        //         continue;
        //     }

        //     foreach ( $term_ids as $term_id ) {



        //         $subscribed = get_term_meta( $term_id, 'subscribed_users', true );

        //         if ( is_array( $subscribed ) && in_array( $user_id, $subscribed, true ) ) {

                    

        //             // Remove user ID
        //             $subscribed = array_diff( $subscribed, [ $user_id ] );

        //             Messages::pri( $term_id );
        //             return;

        //             // Update term meta
        //             update_term_meta( $term_id, 'subscribed_users', $subscribed );
        //         }
        //     }
        // }

    }

    /**
     * Handle notifications when a directory post is saved.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @param bool    $update  Whether this is an update.
     */
    public function save_at_biz_dir( $post_id, $post, $update ) {

        // --------------------------
        // Ignore autosaves and revisions
        // --------------------------
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        // --------------------------
        // Only target 'at_biz_dir' published posts
        // --------------------------
        if ( 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
            return;
        }

        // --------------------------
        // Get selected job/market terms from admin options
        // --------------------------
        $selected_market_term = (array) get_option( 'dns_market_terms', [] );
        $selected_job_term    = (array) get_option( 'dns_job_terms', [] );

        // Merge all selected terms
        $selected_terms = array_merge( $selected_market_term, $selected_job_term );
        $selected_terms = array_filter( $selected_terms ); // remove empty values

        if ( empty( $selected_terms ) ) {
            return; // Nothing to compare
        }

        // --------------------------
        // Get post's directory type terms
        // --------------------------
        $post_terms = wp_get_post_terms( $post_id, ATBDP_DIRECTORY_TYPE, [ 'fields' => 'ids' ] );

        // --------------------------
        // Skip post if it doesn't belong to selected job/market terms
        // --------------------------
        $intersect = array_intersect( $post_terms, $selected_terms );
        if ( empty( $intersect ) ) {
            return;
        }

        // --------------------------
        // Get users subscribed to the post's categories and locations
        // --------------------------
        $taxonomies = [ ATBDP_CATEGORY, ATBDP_LOCATION ];
        $user_ids   = dns_get_subscribed_users_by_post( $post_id, $taxonomies );

        if ( empty( $user_ids ) ) {
            return;
        }

        // --------------------------
        // Get users already notified for this post
        // --------------------------
        $notified_users = get_post_meta( $post_id, '_notified_users', true );
        if ( ! is_array( $notified_users ) ) {
            $notified_users = [];
        }

        // --------------------------
        // Determine new users to notify
        // --------------------------
        $new_users = array_diff( $user_ids, $notified_users );
        if ( empty( $new_users ) ) {
            return; // All users already notified
        }

        // --------------------------
        // Send notifications to new users
        // --------------------------
        foreach ( $new_users as $user_id ) {
            dns_send_listing_notification( $user_id, $post_id );
        }

        // --------------------------
        // Update post meta so we don't notify the same users again
        // --------------------------
        $updated_users = array_merge( $notified_users, $new_users );
        // update_post_meta( $post_id, '_notified_users', array_unique( $updated_users ) );

        // --------------------------
        // Optional: queue emails only for new users
        // --------------------------
        $this->queue_subscription_emails( $post_id, $new_users );
    }


    /**
     * Queue subscription emails using transient + WP Cron
     *
     * @param int   $post_id Post ID.
     * @param array $user_ids Array of user IDs to notify.
    */
    private function queue_subscription_emails( $post_id, $user_ids ) {

        if ( empty( $user_ids ) ) {
            return;
        }

        $queue = get_transient('dns_email_queue');
        if ( ! is_array($queue) ) {
            $queue = [];
        }

        $listing_title = get_the_title($post_id);
        $listing_link  = get_permalink($post_id);

        $listing_types = wp_get_post_terms($post_id, 'atbdp_listing_types', ['fields'=>'names']);
        $listing_types = ! is_wp_error($listing_types) ? $listing_types : [];

        $listing_cities = wp_get_post_terms($post_id, 'at_biz_dir-location', ['fields'=>'names']);
        $listing_cities = ! is_wp_error($listing_cities) ? $listing_cities : [];

        foreach ( $user_ids as $user_id ) {
            $user_info = get_userdata($user_id);
            if ( ! $user_info || empty($user_info->user_email) ) continue;

            // --- Get user-specific email template ---
            $email_subject = get_user_meta( $user_id, 'dns_email_subject', true );
            $email_body    = get_user_meta( $user_id, 'dns_email_body', true );

            // Fallback to default if user hasn't set
            if ( empty( $email_subject ) ) {
                $email_subject = 'New Listing Match Found: {listing_title}';
            }
            if ( empty( $email_body ) ) {
                $email_body = '
                    <p>Hello {user_name},</p>
                    <p>A new listing "{listing_title}" matches your preferences.</p>
                    <p>Type: {listing_types}</p>
                    <p>City: {listing_cities}</p>
                    <p><a href="{listing_link}">View Listing</a></p>
                    <p><a href="{unsubscribe_url}">Unsubscribe</a></p>
                ';
            }

            // --- Placeholders ---
            $placeholders = [
                '{user_name}'      => $user_info->display_name,
                '{listing_title}'  => $listing_title,
                '{listing_link}'   => $listing_link,
                '{listing_types}'  => implode(', ', $listing_types),
                '{listing_cities}' => implode(', ', $listing_cities),
                '{unsubscribe_url}'=> add_query_arg([
                    'dns_unsubscribe'=>1,
                    'user_id'=>$user_id,
                    'nonce'=>wp_create_nonce('dns_unsubscribe_'.$user_id),
                ], site_url())
            ];

            // Replace placeholders if they exist in template
            $subject = strtr( $email_subject, $placeholders );
            $message = strtr( $email_body, $placeholders );

            // Add email to queue
            $queue[] = [
                'to'=>$user_info->user_email,
                'subject'=>$subject,
                'message'=>$message,
                'headers'=>['Content-Type: text/html; charset=UTF-8'],
            ];
        }

        set_transient('dns_email_queue', $queue, HOUR_IN_SECONDS);

        if ( ! wp_next_scheduled('dns_process_email_queue') ) {
            wp_schedule_single_event(time()+30, 'dns_process_email_queue');
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

        // 1. Only proceed when the query params are present
        if ( ! isset( $_GET['dns_unsubscribe'], $_GET['user_id'], $_GET['nonce'] ) ) {
            return;
        }

        // 2. Sanitize inputs
        $user_id = absint( $_GET['user_id'] );
        $nonce   = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

        if ( ! $user_id ) {
            return; // invalid user id
        }

        // 3. Verify nonce – make sure you used the same action when creating it
        if ( ! wp_verify_nonce( $nonce, 'dns_unsubscribe_' . $user_id ) ) {
            wp_die( esc_html__( 'Invalid request.', 'directorist-notification-system' ) );
        }

        

        // 4. Remove user from all subscriptions
        if ( function_exists( 'dns_unsubscribe_user' ) ) {
            dns_unsubscribe_user( $user_id );
        }

        // 5. Redirect safely back to subscription page (or fallback)
        $subscription_id = (int) get_option( 'dns_subscription_page_id' );
        $redirect_url    = $subscription_id ? get_permalink( $subscription_id ) : home_url( '/' );

        // Optional: add a query arg so you can show a "You have been unsubscribed" notice
        $redirect_url = add_query_arg(
            'dns_unsubscribed',
            '1',
            $redirect_url
        );

        wp_safe_redirect( $redirect_url );
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
