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
        add_action( 'dns_process_email_queue', [ $this, 'dns_process_email_queue' ] );

        // --------------------------
        // Unsubscribe link handler
        // --------------------------
        add_action( 'template_redirect', [ $this, 'check_unsubscribe' ] );  

        // Optional head action
        add_action( 'wp_head', [ $this, 'head' ] );

        add_filter( 'bp_notifications_get_notifications_for_user', [ $this, 'dns_format_listing_notifications'], 10, 7 );

    }

    /**
     * Optional head action (currently disabled, placeholder for future use)
     */
    public function head() {

        // $post_id = 11180729;
        // $user_ids = [ 213, 224, 246 ];
        // $result = $this->dns_send_listing_notification_emails( $post_id, $user_ids );
        // Messages::pri( $result );

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
        $user_ids = dns_find_matching_users_for_post( $post_id );

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
        // LIGHTWEIGHT: Just queue post_id and user_ids
        // --------------------------
        $queue = get_transient( 'dns_email_queue' );
        $queue = is_array( $queue ) ? $queue : [];
        
        // Add to queue (just post_id and user_ids)
        $queue[] = [
            'post_id'  => $post_id,
            'user_ids' => $new_users,
        ];
        
        set_transient( 'dns_email_queue', $queue, HOUR_IN_SECONDS );

        // --------------------------
        // Send BuddyPress notifications to new users
        // --------------------------
        foreach ( $new_users as $user_id ) {
            if ( function_exists( 'dns_send_listing_notification' ) ) {
                dns_send_listing_notification( $user_id, $post_id );
            }
        }

        // --------------------------
        // Update post meta to avoid notifying the same users again
        // --------------------------
        $updated_users = array_merge( $notified_users, $new_users );
        update_post_meta( $post_id, '_notified_users', array_unique( $updated_users ) );

        // --------------------------
        // SCHEDULE CRON
        // --------------------------
        if ( ! wp_next_scheduled( 'dns_process_email_queue' ) ) {
            wp_schedule_single_event( time() + 30, 'dns_process_email_queue' );
        }
    }

    /**
     * Process email queue (called by cron)
     * NOW: Gets all data when processing
     */
    public function dns_process_email_queue() {
        $queue = get_transient( 'dns_email_queue' );
        
        if ( empty( $queue ) || ! is_array( $queue ) ) {
            return;
        }
        
        $batch_size = apply_filters( 'dns_email_batch_size', 10 ); // Process 10 posts at a time
        $processed_count = 0;
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ( $queue as $index => $item ) {
            if ( $processed_count >= $batch_size ) {
                break;
            }
            
            $post_id  = $item['post_id'];
            $user_ids = $item['user_ids'];
            
            // --------------------------
            // NOW GET ALL LISTING DATA
            // --------------------------
            $listing_types = wp_get_post_terms( $post_id, ATBDP_TYPE, [ 'fields' => 'names' ] );
            $categories    = wp_get_post_terms( $post_id, ATBDP_CATEGORY, [ 'fields' => 'names' ] );
            $locations     = wp_get_post_terms( $post_id, ATBDP_LOCATION, [ 'fields' => 'names' ] );
            
            // Handle WP_Error from taxonomy queries
            if ( is_wp_error( $listing_types ) ) $listing_types = [];
            if ( is_wp_error( $categories ) ) $categories = [];
            if ( is_wp_error( $locations ) ) $locations = [];
            
            $listing_data = [
                'title'    => get_the_title( $post_id ),
                'link'     => get_permalink( $post_id ),
                'type'     => implode( ', ', $listing_types ),
                'category' => implode( ', ', $categories ),
                'location' => implode( ', ', $locations ),
            ];
            
            // --------------------------
            // GET EMAIL TEMPLATES
            // --------------------------
            $email_subject = get_option(
                'dns_email_default_subject',
                __( 'New Listing Match Found: {listing_title}', 'directorist-notification-system' )
            );
            
            $email_body = get_option(
                'dns_email_default_body',
                'Hello {user_name},

A new listing matching your preferences has been posted:

{listing_title}

Type: {listing_types}
Category: {listing_category}
Location: {listing_cities}

View Listing: {listing_link}

---
You are receiving this because you subscribed to listing alerts.
Unsubscribe: {unsubscribe_url}

© 2026 {site_name}. All rights reserved.'
            );
            
            // --------------------------
            // SEND EMAILS TO ALL USERS FOR THIS POST
            // --------------------------
            foreach ( $user_ids as $user_id ) {
                $user = get_userdata( $user_id );
                
                // Skip invalid users or users without email
                if ( ! $user || empty( $user->user_email ) ) {
                    continue;
                }
                
                // Skip if user has unsubscribed
                if ( get_user_meta( $user_id, 'dns_unsubscribed', true ) ) {
                    continue;
                }
                
                // Build placeholders for this user
                $placeholders = [
                    '{user_name}'        => esc_html( $user->display_name ),
                    '{listing_title}'    => esc_html( $listing_data['title'] ),
                    '{listing_link}'     => esc_url( $listing_data['link'] ),
                    '{listing_types}'    => esc_html( $listing_data['type'] ),
                    '{listing_category}' => esc_html( $listing_data['category'] ),
                    '{listing_cities}'   => esc_html( $listing_data['location'] ),
                    '{unsubscribe_url}'  => esc_url( dns_get_unsubscribe_url( $user_id ) ),
                    '{site_name}'        => esc_html( get_bloginfo( 'name' ) ),
                ];
                
                // Replace placeholders
                $subject = strtr( $email_subject, $placeholders );
                $message = strtr( $email_body, $placeholders );
                
                // Send email
                $result = wp_mail(
                    sanitize_email( $user->user_email ),
                    $subject,
                    $message,
                    [
                        'Content-Type: text/plain; charset=UTF-8',
                        'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
                    ]
                );
                
                if ( $result ) {
                    $sent_count++;
                    do_action( 'dns_email_sent', $user_id, $post_id );
                } else {
                    $failed_count++;
                    do_action( 'dns_email_failed', $user_id, $post_id );
                    error_log( 'DNS: Failed to send email to ' . $user->user_email . ' for post ' . $post_id );
                }
                
                // Small delay to prevent rate limiting
                usleep( 100000 ); // 0.1 second
            }
            
            // Remove this item from queue
            unset( $queue[ $index ] );
            $processed_count++;
        }
        
        // Reindex array
        $queue = array_values( $queue );
        
        // Save remaining queue or delete if empty
        if ( ! empty( $queue ) ) {
            set_transient( 'dns_email_queue', $queue, HOUR_IN_SECONDS );
            
            // Schedule next batch
            if ( ! wp_next_scheduled( 'dns_process_email_queue' ) ) {
                wp_schedule_single_event( time() + 30, 'dns_process_email_queue' );
            }
        } else {
            delete_transient( 'dns_email_queue' );
        }
        
        // Log batch completion
        do_action( 'dns_email_batch_processed', $processed_count, $sent_count, $failed_count, count( $queue ) );
    }

    
    /**
     * Handle global unsubscribe requests
     */
    public function check_unsubscribe() {

        // Check if required parameters exist
        if ( ! isset( $_GET['dns_unsubscribe'], $_GET['user_id'], $_GET['nonce'] ) ) {
            return;
        }

        $user_id = absint( $_GET['user_id'] );
        $nonce   = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

        // Validate user ID and nonce
        if ( ! $user_id || ! wp_verify_nonce( $nonce, 'dns_unsubscribe_' . $user_id ) ) {
            // Invalid request
            add_action( 'the_content', function( $content ) {
                return '<div class="dns-error" style="color:red; font-weight:bold; margin:10px 0;">' .
                    esc_html__( 'Invalid unsubscribe request.', 'directorist-notification-system' ) .
                    '</div>' . $content;
            });
            return;
        }

        // Remove user from all subscriptions if function exists
        if ( function_exists( 'dns_unsubscribe_user' ) ) {
            $already_unsubscribed = ! dns_unsubscribe_user( $user_id );

            if ( $already_unsubscribed ) {
                add_action( 'the_content', function( $content ) {
                    return '<div class="dns-info" style="color:orange; font-weight:bold; margin:10px 0;">' .
                        esc_html__( 'You are already unsubscribed.', 'directorist-notification-system' ) .
                        '</div>' . $content;
                });
                return;
            }
        } else {
            // Fallback: Just set unsubscribed meta
            update_user_meta( $user_id, 'dns_unsubscribed', 1 );
        }

        // Redirect back to subscription page with confirmation
        $subscription_id = (int) get_option( 'dns_subscription_page_id' );
        $redirect_url    = $subscription_id ? get_permalink( $subscription_id ) : home_url( '/' );
        $redirect_url    = add_query_arg( 'dns_unsubscribed', '1', $redirect_url );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Format listing notifications for BuddyPress
     *
     * @param string $content
     * @param int    $user_id
     * @param string $format
     * @param string $action
     * @param string $component
     * @param int    $item_id
     * @param int    $secondary_item_id
     * @return string|array
     */
    public function dns_format_listing_notifications( $content, $user_id, $format, $action, $component, $item_id, $secondary_item_id ) {

        if ( $component !== 'activity' || $action !== 'dns_new_listing_match' ) {
            return $content;
        }

        $listing_title = get_the_title( $item_id );
        $listing_link  = get_permalink( $item_id );

        // Unsubscribe URL
        $unsubscribe_url = dns_get_unsubscribe_url( $user_id );

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
                esc_html( sprintf( __( 'New listing match: %s', 'directorist-notification-system' ), $listing_title ) ),
                esc_url( $unsubscribe_url ),
                esc_html__( 'Unsubscribe', 'directorist-notification-system' )
            );
        }

        return [
            'text' => sprintf( __( 'New listing match: %s', 'directorist-notification-system' ), $listing_title ),
            'link' => $listing_link,
        ];
    }
}