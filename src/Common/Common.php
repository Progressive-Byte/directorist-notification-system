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

        // Only target specific post type and published posts
        if ( 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
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
            return; // Skip if post doesn't belong
        }

        // --- Gather all subscribers ---
        $user_ids = [];

        $data = dns_get_post_data( $post_id );

        // Subscribers from post meta
        if ( ! empty( $data['subscribed_users'] ) ) {
            $user_ids = array_merge( $user_ids, $data['subscribed_users'] );
        }

        // Subscribers from terms
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

        // --- Get users already notified for this post ---
        $notified_users = get_post_meta( $post_id, '_notified_users', true );
        if ( ! is_array( $notified_users ) ) {
            $notified_users = [];
        }

        // --- Send notifications only to users not notified yet ---
        foreach ( $user_ids as $user_id ) {
            if ( in_array( $user_id, $notified_users, true ) ) {
                continue; // Already notified
            }

            // Send notification
            dns_send_listing_notification( $user_id, $post_id );

            // Add user to notified list
            $notified_users[] = $user_id;
        }

        // --- Update post meta so we don't notify same users again ---
        update_post_meta( $post_id, '_notified_users', array_unique( $notified_users ) );

        // Optional: queue emails
        $this->queue_subscription_emails( $post_id, $user_ids );
    }






    /**
     * Queue subscription emails using transient + WP Cron
     */
    private function queue_subscription_emails( $post_id, $user_ids ) {

    // Existing queue from transient
    $queue = get_transient( 'dns_email_queue' );
    if ( ! is_array( $queue ) ) {
        $queue = [];
    }

    $listing_title = get_the_title( $post_id );
    $listing_link  = get_permalink( $post_id );

    // Get listing type and city (names)
    $listing_types = wp_get_post_terms(
        $post_id,
        'atbdp_listing_types',
        ['fields' => 'names']
    );
    if ( is_wp_error( $listing_types ) ) {
        $listing_types = [];
    }

    $listing_cities = wp_get_post_terms(
        $post_id,
        'at_biz_dir-location',
        ['fields' => 'names']
    );
    if ( is_wp_error( $listing_cities ) ) {
        $listing_cities = [];
    }

    foreach ( $user_ids as $user_id ) {
        $user_info = get_userdata( $user_id );
        if ( ! $user_info || empty( $user_info->user_email ) ) {
            continue;
        }

        // Unsubscribe URL
        $unsubscribe_url = add_query_arg(
            [
                'dns_unsubscribe' => 1,
                'user_id'         => $user_id,
                'nonce'           => wp_create_nonce( 'dns_unsubscribe_' . $user_id ),
            ],
            site_url()
        );

        // Build HTML email using output buffering for cleaner template
        ob_start();
        ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html( $listing_title ); ?></title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #f3f4f6;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                }
                a {
                    color: #2563eb;
                    text-decoration: none;
                }
                @media only screen and (max-width: 600px) {
                    .dns-email-card {
                        width: 100% !important;
                        border-radius: 0 !important;
                    }
                    .dns-email-inner {
                        padding: 18px !important;
                    }
                }
            </style>
            </head>
            <body style="margin:0;padding:0;background-color:#f3f4f6;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;padding:24px 0;">
                    <tr>
                        <td align="center">
                            <table class="dns-email-card" width="600" cellpadding="0" cellspacing="0" border="0"
                                   style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;
                                          box-shadow:0 18px 45px rgba(15,23,42,0.10);border:1px solid #e5e7eb;
                                          overflow:hidden;">
                                <!-- Header -->
                                <tr>
                                    <td style="background:linear-gradient(135deg,#2563eb,#22c55e);padding:18px 24px;">
                                        <h1 style="margin:0;font-size:20px;font-weight:600;color:#ffffff;">
                                            <?php esc_html_e( 'New Listing Match Found', 'directorist-notification-system' ); ?>
                                        </h1>
                                    </td>
                                </tr>

                                <!-- Body -->
                                <tr>
                                    <td class="dns-email-inner" style="padding:24px 24px 20px;">
                                        <p style="margin:0 0 14px;font-size:14px;color:#111827;">
                                            <?php
                                            printf(
                                                /* translators: %s: user display name */
                                                esc_html__( 'Hello %s,', 'directorist-notification-system' ),
                                                '<strong>' . esc_html( $user_info->display_name ) . '</strong>'
                                            );
                                            ?>
                                        </p>

                                        <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.6;">
                                            <?php esc_html_e( 'A new listing has been published that matches your notification preferences.', 'directorist-notification-system' ); ?>
                                        </p>

                                        <table cellpadding="0" cellspacing="0" border="0" width="100%"
                                               style="margin:0 0 18px;border-collapse:collapse;">
                                            <tr>
                                                <td style="padding:12px 14px;border-radius:12px;background-color:#f9fafb;
                                                           border:1px solid #e5e7eb;">

                                                    <!-- Title -->
                                                    <p style="margin:0 0 8px;font-size:14px;color:#111827;">
                                                        <strong><?php esc_html_e( 'Title:', 'directorist-notification-system' ); ?></strong>
                                                        <a href="<?php echo esc_url( $listing_link ); ?>" style="color:#2563eb;text-decoration:none;">
                                                            <?php echo esc_html( $listing_title ); ?>
                                                        </a>
                                                    </p>

                                                    <!-- Type -->
                                                    <?php if ( ! empty( $listing_types ) ) : ?>
                                                        <p style="margin:0 0 6px;font-size:13px;color:#4b5563;">
                                                            <strong><?php esc_html_e( 'Type:', 'directorist-notification-system' ); ?></strong>
                                                            <?php echo esc_html( implode( ', ', $listing_types ) ); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <!-- City -->
                                                    <?php if ( ! empty( $listing_cities ) ) : ?>
                                                        <p style="margin:0;font-size:13px;color:#4b5563;">
                                                            <strong><?php esc_html_e( 'City:', 'directorist-notification-system' ); ?></strong>
                                                            <?php echo esc_html( implode( ', ', $listing_cities ) ); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                </td>
                                            </tr>
                                        </table>

                                        <!-- View Listing button -->
                                        <p style="margin:0 0 22px;text-align:left;">
                                            <a href="<?php echo esc_url( $listing_link ); ?>"
                                               style="display:inline-block;padding:10px 20px;border-radius:999px;
                                                      background:linear-gradient(135deg,#2563eb,#22c55e);
                                                      color:#ffffff;font-size:14px;font-weight:600;
                                                      text-decoration:none;box-shadow:0 10px 20px rgba(37,99,235,0.35);">
                                                <?php esc_html_e( 'View Listing', 'directorist-notification-system' ); ?>
                                            </a>
                                        </p>

                                        <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 16px;">

                                        <!-- Unsubscribe -->
                                        <p style="margin:0 0 10px;font-size:12px;color:#6b7280;line-height:1.5;">
                                            <?php esc_html_e( 'If you no longer want to receive notifications like this, you can unsubscribe at any time:', 'directorist-notification-system' ); ?>
                                        </p>

                                        <p style="margin:0 0 10px;">
                                            <a href="<?php echo esc_url( $unsubscribe_url ); ?>"
                                               style="display:inline-block;padding:8px 18px;border-radius:999px;
                                                      background-color:#ef4444;color:#ffffff;font-size:12px;
                                                      font-weight:600;text-decoration:none;">
                                                <?php esc_html_e( 'Unsubscribe from all notifications', 'directorist-notification-system' ); ?>
                                            </a>
                                        </p>

                                        <p style="margin:0;font-size:11px;color:#9ca3af;line-height:1.5;">
                                            <?php esc_html_e( 'You are receiving this email because you subscribed to listing notifications.', 'directorist-notification-system' ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <!-- Footer -->
                                <tr>
                                    <td style="padding:14px 24px;background-color:#f9fafb;border-top:1px solid #e5e7eb;">
                                        <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;">
                                            &copy; <?php echo esc_html( date( 'Y' ) ); ?>
                                            <?php echo esc_html( get_bloginfo( 'name' ) ); ?>.
                                            <?php esc_html_e( 'All rights reserved.', 'directorist-notification-system' ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
                    <?php
                    $message = ob_get_clean();

                    $queue[] = [
                        'to'      => $user_info->user_email,
                        'subject' => sprintf(
                            /* translators: %s: listing title */
                            __( 'New Listing Match Found: %s', 'directorist-notification-system' ),
                            $listing_title
                        ),
                        'message' => $message,
                        'headers' => [ 'Content-Type: text/html; charset=UTF-8' ],
                    ];
                }

                // Save back to transient
                set_transient( 'dns_email_queue', $queue, HOUR_IN_SECONDS );

                // Schedule processing event if not already scheduled
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
        if ( function_exists( 'dns_remove_user_from_subscriptions' ) ) {
            dns_remove_user_from_subscriptions( $user_id );
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
