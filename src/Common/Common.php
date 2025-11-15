<?php
namespace DNS\Common;
use DNS\Helper\Messages;

if (!defined('ABSPATH')) exit;


class Common {

	/**
     * Constructor.
     */
    public function __construct() {

        add_action( 'save_post', [ $this, 'save_at_biz_dir' ], '999', 3 );
        add_action( 'wp_head', [ $this, 'head'] );
        
    }

    public function head( ){

        // $taxonomies             = [ 'atbdp_listing_types', 'at_biz_dir-location' ];
        // // $all_taxonomies_data    = dns_get_terms_with_subscribers( $taxonomies );
    	// // // Messages::pri( 'Hi' );
        // $post_id = 706;
    	// $data = dns_get_post_data( $post_id );

        // $user_ids = [];

        // // From post meta
        // if ( ! empty( $data['subscribed_users'] ) ) {
        //     $user_ids = array_merge( $user_ids, $data['subscribed_users'] );
        // }

        // // From terms
        // if ( ! empty( $data['terms'] ) ) {
        //     foreach ( $data['terms'] as $taxonomy => $terms ) {
        //         foreach ( $terms as $term_id => $term_data ) {
        //             if ( ! empty( $term_data['subscribed_users'] ) ) {
        //                 $user_ids = array_merge( $user_ids, $term_data['subscribed_users'] );
        //             }
        //         }
        //     }
        // }

        // if ( empty( $user_ids ) ) {
        //     $taxonomy_data = dns_get_terms_with_subscribers( $taxonomies );
        //     $user_ids      = dns_extract_user_ids_from_taxonomy_data( $taxonomy_data );
        // }


        // Messages::pri( $user_ids );

        

		
    }
    /**
     * Fires when a post is created or updated.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @param bool    $update  Whether this is an update or new post.
     */

    function save_at_biz_dir( $post_id, $post, $update ) {

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

        // 🚀 Call separate email function
       $this->dns_send_subscription_email( $post_id, $user_ids );

        // Mark as sent
        update_post_meta( $post_id, '_dns_email_sent', 1 );
    }

   private function dns_send_subscription_email( $post_id, $user_ids ) {

        foreach ( $user_ids as $user_id ) {
            $user_info = get_userdata( $user_id );

            if ( $user_info && ! empty( $user_info->user_email ) ) {

                $to      = $user_info->user_email;
                $subject = 'New Post Published: ' . get_the_title( $post_id );

                $message  = 'Hello ' . $user_info->display_name . ",\n\n";
                $message .= 'A new post has been published that matches your subscription preferences:' . "\n";
                $message .= get_permalink( $post_id ) . "\n\n";
                $message .= 'Thank you!';

                wp_mail( $to, $subject, $message );
            }
        }
    }


}
