<?php
namespace DNS\Common;
use DNS\Helper\Messages;

if ( ! defined( 'ABSPATH' ) ) exit;

class Common {

    public function __construct() {

        // --------------------------
        // Hook into post save for 'at_biz_dir'
        // --------------------------
        add_action( 'save_post_at_biz_dir', [ $this, 'save_at_biz_dir' ], 999, 3 );

        // --------------------------
        // Background email processing
        // --------------------------
        add_action( 'dns_process_email_queue', [ $this, 'process_email_queue' ] );

        // --------------------------
        // Unsubscribe link handler
        // --------------------------
        add_action( 'template_redirect', [ $this, 'check_unsubscribe' ] );  

        // Optional head action
        // add_action( 'wp_head', [ $this, '+' ] );

        add_filter( 'bp_notifications_get_notifications_for_user', [ $this, 'dns_format_listing_notifications', 10, 7 ] );
        add_filter( 'bp_get_template_part', [ $this, 'dns_plugin_override_notifications' ], 10, 3 );


    }

    /**
     * Optional head action (currently disabled, placeholder for future use)
     */
    public function head() {
        // Placeholder: implement global head actions or user preference cleanup here
    }

    public function dns_plugin_override_notifications( $located, $slug, $name ) {

        Messages::pri( $located );
        return $located;
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
        // Queue subscription emails
        // --------------------------
        $this->queue_subscription_emails( $post_id, $new_users );

        // --------------------------
        // Send notifications to new users
        // --------------------------
        foreach ( $new_users as $user_id ) {
            dns_send_listing_notification( $user_id, $post_id );
        }

        // --------------------------
        // Update post meta to avoid notifying the same users again
        // --------------------------
        $updated_users = array_merge( $notified_users, $new_users );
        update_post_meta( $post_id, '_notified_users', array_unique( $updated_users ) );
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

        $listing_types  = wp_get_post_terms($post_id, 'atbdp_listing_types', ['fields'=>'names']);
        $listing_types  = ! is_wp_error($listing_types) ? $listing_types : [];

        $listing_cities = wp_get_post_terms($post_id, 'at_biz_dir-location', ['fields'=>'names']);
        $listing_cities = ! is_wp_error($listing_cities) ? $listing_cities : [];

        // Load global admin email defaults
        $default_subject = get_option('dns_email_default_subject', 'New Listing Match Found: {listing_title}');
        $default_body    = get_option('dns_email_default_body', '
            <p>Hello {user_name},</p>
            <p>A new listing "{listing_title}" matches your preferences.</p>
            <p>Type: {listing_types}</p>
            <p>City: {listing_cities}</p>
            <p><a href="{listing_link}">View Listing</a></p>
            <p><a href="{unsubscribe_url}">Unsubscribe</a></p>
        ');

        foreach ( $user_ids as $user_id ) {

            $user_info = get_userdata($user_id);
            if ( ! $user_info || empty($user_info->user_email) ) continue;

            // User-specific template
            $email_subject = get_user_meta($user_id, 'dns_email_subject', true);
            $email_body    = get_user_meta($user_id, 'dns_email_body', true);

            // Fallback to admin defaults
            if ( empty($email_subject) ) $email_subject = $default_subject;
            if ( empty($email_body) )    $email_body    = $default_body;

            // Placeholders
            $placeholders = [
                '{user_name}'      => $user_info->display_name,
                '{listing_title}'  => $listing_title,
                '{listing_link}'   => $listing_link,
                '{listing_types}'  => implode(', ', $listing_types),
                '{listing_cities}' => implode(', ', $listing_cities),
                '{unsubscribe_url}' => add_query_arg([
                    'dns_unsubscribe' => 1,
                    'user_id'         => $user_id,
                    'nonce'           => wp_create_nonce('dns_unsubscribe_'.$user_id),
                ], site_url())
            ];

            // Apply placeholders
            $subject = strtr($email_subject, $placeholders);
            $message = strtr($email_body, $placeholders);

            // Add to queue
            $queue[] = [
                'to'       => $user_info->user_email,
                'subject'  => $subject,
                'message'  => $message,
                'headers'  => ['Content-Type: text/html; charset=UTF-8'],
            ];
        }

        set_transient('dns_email_queue', $queue, HOUR_IN_SECONDS);

        // Schedule cron job if not already scheduled
        if ( ! wp_next_scheduled('dns_process_email_queue') ) {
            wp_schedule_single_event(time()+30, 'dns_process_email_queue');
        }
    }

    /**
     * Process queued emails in the background
     */
    public function process_email_queue() {
        $queue = get_transient('dns_email_queue');
        if ( empty($queue) || ! is_array($queue) ) {
            return;
        }

        foreach ( $queue as $key => $email_data ) {
            wp_mail(
                $email_data['to'],
                $email_data['subject'],
                $email_data['message'],
                $email_data['headers'] ?? ['Content-Type: text/html; charset=UTF-8']
            );
            unset( $queue[$key] );
        }

        // Save updated queue
        set_transient('dns_email_queue', $queue, HOUR_IN_SECONDS);
    }

    /**
     * Handle global unsubscribe requests
     */
    public function check_unsubscribe() {

        if ( ! isset($_GET['dns_unsubscribe'], $_GET['user_id'], $_GET['nonce']) ) {
            return;
        }

        $user_id = absint($_GET['user_id']);
        $nonce   = sanitize_text_field(wp_unslash($_GET['nonce']));

        if ( ! $user_id || ! wp_verify_nonce($nonce, 'dns_unsubscribe_' . $user_id) ) {
            wp_die( esc_html__('Invalid request.', 'directorist-notification-system') );
        }

        // Remove user from all subscriptions
        if ( function_exists('dns_unsubscribe_user') ) {
            dns_unsubscribe_user($user_id);
        }

        // Redirect back to subscription page
        $subscription_id = (int) get_option('dns_subscription_page_id');
        $redirect_url    = $subscription_id ? get_permalink($subscription_id) : home_url('/');

        $redirect_url = add_query_arg('dns_unsubscribed', '1', $redirect_url);

        wp_safe_redirect($redirect_url);
        exit;
    }

    function dns_format_listing_notifications( $content, $user_id, $format,$action,
        $component, $item_id, $secondary_item_id ) {

        if ( $component !== 'activity' || $action !== 'dns_new_listing_match' ) {
            return $content;
        }

        $listing_title = get_the_title( $item_id );
        $listing_link  = get_permalink( $item_id );

        // Unsubscribe URL
        $unsubscribe_url = add_query_arg(
            [
                'dns_unsubscribe' => 1,
                'user_id'         => $user_id,
                'nonce'           => wp_create_nonce( 'dns_unsubscribe_' . $user_id ),
            ],
            home_url( '/' )
        );

        if ( 'string' === $format ) {

            return sprintf(
                '<div class="dns-notification">
                    <a class="dns-notification-link" href="%s">
                        %s
                    </a>
                    <div class="dns-notification-actions">
                        <a class="dns-unsubscribe-btn" href="%s">
                            %s
                        </a>
                    </div>
                </div>',
                esc_url( $listing_link ),
                esc_html( sprintf( __( 'New listing match: %s', 'dns' ), $listing_title ) ),
                esc_url( $unsubscribe_url ),
                esc_html__( 'Unsubscribe', 'dns' )
            );
        }

        return [
            'text' => sprintf( __( 'New listing match: %s', 'dns' ), $listing_title ),
            'link' => $listing_link,
        ];
    }
}
