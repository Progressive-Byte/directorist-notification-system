<?php
namespace DNS\Common;
use DNS\Helper\Messages;

if (!defined('ABSPATH')) exit;


class Common {

	/**
     * Constructor.
     */
    public function __construct() {

        add_action( 'save_post', [ $this, 'save_at_biz_dir' ], 999, 3 );
        add_action( 'wp_head', [ $this, 'head'] );
        
    }

    public function head( ){
    	// Messages::pri( 'Hi' );

    	// $taxonomies = [ 'atbdp_listing_types', 'at_biz_dir-location' ];
        // $all_data = dns_get_terms_data( $taxonomies );
		// Messages::pri( $term->name . ' (ID: ' . $term->term_id . ')<br>' );

		// foreach ($terms as $term) {
		// }
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

        if ( ! in_array( $post->post_type, [ 'post', 'page', 'at_biz_dir' ], true ) ) {
            return; // Only target specific post types
        }

        if ( 'publish' !== $post->post_status ) {
            return;
        }        

        // Example: Add custom logic here
        // dns_update_post_subscribed_users( $post_id );
    }

}
