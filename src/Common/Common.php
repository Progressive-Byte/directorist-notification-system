<?php
namespace DNS\Common;
use DNS\Helper\Messages;

if ( ! defined( 'ABSPATH' ) ) exit;

class Common {

    /**
     * Constructor.
     */
    public function __construct() {

        // Hook into post save
        add_action( 'save_post', [ $this, 'save_at_biz_dir' ], 999, 3 );

        // Hook for background email processing
        add_action( 'dns_process_email_queue', [ $this, 'process_email_queue' ] );
    }

    /**
     * Fires when a post is created or updated.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @param bool    $update  Whether this is an update or new post.
     */
    public function save_at_biz_dir( $post_id, $post, $update ) {

        // Avoid auto-saves and revisions
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Only target specific post types
        if ( ! in_array( $post->post_type, [ 'post', 'page', 'at_biz_dir' ], true ) ) {
            return;
        }

        // Only run when post is published
        if ( 'publish' !== $post->post_status ) {
            return;
        }

        // Check if emails were already sent
        if ( get_post_meta( $post_id, '_dns_email_sent', true ) ) {
            return; // Already sent
        }

        // Get all subscribed users
        $data = dns_get_post_data( $post_id );

        $user_ids = [];

        // From post meta
        if ( ! empty( $data['subscribed_users'] ) ) {
            $user_ids = array_merge( $user_ids, $data['subscribed_users'] );
        }

        // From terms
        if ( ! empty( $data['terms'] ) ) {
            foreach ( $data['terms'] as $taxonomy => $terms ) {
                foreach ( $terms as $term_id => $term_data ) {
                    if ( ! empty( $term_data['subscribed_users'] ) ) {
                        $user_ids = array_merge( $user_ids, $term_data['subscribed_users'] );
                    }
                }
            }
        }

        // Remove duplicates
        $user_ids = array_unique( $user_ids );

        // If still empty → get default taxonomy subscribers
        if ( empty( $user_ids ) ) {
            $taxonomies     = [ 'atbdp_listing_types', 'at_biz_dir-location' ];
            $taxonomy_data  = dns_get_terms_with_subscribers( $taxonomies );
            $user_ids       = dns_extract_user_ids_from_taxonomy_data( $taxonomy_data );
        }

        // Still empty? nothing to send
        if ( empty( $user_ids ) ) {
            return;
        }

        // Queue emails instead of sending immediately
        $this->queue_subscription_emails( $post_id, $user_ids );

        // Mark as sent
        update_post_meta( $post_id, '_dns_email_sent', 1 );
    }

    /**
     * Queue subscription emails using transient + WP Cron
     */
    private function queue_subscription_emails( $post_id, $user_ids ) {

        $queue = get_transient( 'dns_email_queue' );
        if ( ! is_array( $queue ) ) {
            $queue = [];
        }

        foreach ( $user_ids as $user_id ) {
            $user_info = get_userdata( $user_id );
            if ( ! $user_info || empty( $user_info->user_email ) ) {
                continue;
            }

            $queue[] = [
                'post_id' => $post_id,
                'user_id' => $user_id,
                'to'      => $user_info->user_email,
                'subject' => 'New Post Published: ' . get_the_title( $post_id ),
                'message' => 'Hello ' . $user_info->display_name . ",\n\n"
                           . 'A new post has been published that matches your subscription preferences:' . "\n"
                           . get_permalink( $post_id ) . "\n\nThank you!",
            ];
        }

        set_transient( 'dns_email_queue', $queue, HOUR_IN_SECONDS );

        // Schedule background processing
        if ( ! wp_next_scheduled( 'dns_process_email_queue' ) ) {
            wp_schedule_single_event( time() + 60, 'dns_process_email_queue' ); // run after 30s
        }
    }

    /**
     * Process queued emails in the background.
     */
    public function process_email_queue() {
        $queue = get_transient( 'dns_email_queue' );
        if ( empty( $queue ) || ! is_array( $queue ) ) {
            return;
        }

        foreach ( $queue as $key => $email_data ) {
            wp_mail( $email_data['to'], $email_data['subject'], $email_data['message'] );
            unset( $queue[$key] ); // remove from queue
        }

        // Save updated queue
        set_transient( 'dns_email_queue', $queue, HOUR_IN_SECONDS );
    }

}
