<?php
namespace DNS\Common;

if ( ! defined( 'ABSPATH' ) ) exit;

class Common {

    public function __construct() {

        // Hook into post save
        add_action( 'save_post', [ $this, 'save_at_biz_dir' ], 999, 3 );

        // Background email processing
        add_action( 'dns_process_email_queue', [ $this, 'process_email_queue' ] );

        // Unsubscribe link handler
        add_action( 'template_redirect', [ $this, 'check_unsubscribe' ] );
    }

    /**
     * Fires when a post is created or updated
     */
    public function save_at_biz_dir( $post_id, $post, $update ) {

        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( ! in_array( $post->post_type, [ 'post', 'page', 'at_biz_dir' ], true ) ) {
            return;
        }

        if ( 'publish' !== $post->post_status ) {
            return;
        }

        // Avoid duplicate email queue
        if ( get_post_meta( $post_id, '_dns_email_sent', true ) ) {
            return;
        }

        // Get subscribed users
        $data = dns_get_post_data( $post_id );
        $user_ids = [];

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
            $taxonomies    = [ 'atbdp_listing_types', 'at_biz_dir-location' ];
            $taxonomy_data = dns_get_terms_with_subscribers( $taxonomies );
            $user_ids      = dns_extract_user_ids_from_taxonomy_data( $taxonomy_data );
        }

        if ( empty( $user_ids ) ) {
            return;
        }

        // Queue emails for background processing
        $this->queue_subscription_emails( $post_id, $user_ids );

        // Mark as queued
        // update_post_meta( $post_id, '_dns_email_sent', 1 );
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

            // Global unsubscribe link
            $unsubscribe_url = add_query_arg(
                [
                    'dns_unsubscribe' => 1,
                    'user_id'        => $user_id,
                    'nonce'          => wp_create_nonce( 'dns_unsubscribe_' . $user_id ),
                ],
                site_url()
            );

            // HTML message
            $message  = '<p>Hello ' . esc_html( $user_info->display_name ) . ',</p>';
            $message .= '<p>A new post has been published that matches your subscription preferences:</p>';
            $message .= '<p><a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a></p>';
            $message .= '<p>If you wish to unsubscribe from all notifications, click the button below:</p>';
            $message .= '<p><a href="' . esc_url( $unsubscribe_url ) . '" style="display:inline-block;padding:10px 20px;color:#ffffff;background-color:#0073aa;text-decoration:none;border-radius:5px;">Unsubscribe</a></p>';
            $message .= '<p>Thank you!</p>';

            $queue[] = [
                'to'      => $user_info->user_email,
                'subject' => 'New Post Published: ' . get_the_title( $post_id ),
                'message' => $message,
                'headers' => ['Content-Type: text/html; charset=UTF-8'], // Important for HTML
            ];
        }

        set_transient( 'dns_email_queue', $queue, HOUR_IN_SECONDS );

        // Schedule background processing
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
            // Send HTML email
            wp_mail(
                $email_data['to'],
                $email_data['subject'],
                $email_data['message'],
                isset($email_data['headers']) ? $email_data['headers'] : ['Content-Type: text/html; charset=UTF-8']
            );

            // Remove email from queue
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
        remove_user_from_subscriptions( $user_id );

        // Optional: redirect with confirmation
        wp_redirect( home_url( '?unsubscribed=1' ) );
        exit;
    }


}
