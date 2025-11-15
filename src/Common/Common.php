<?php
namespace DNS\Common;
use DNS\Helper\Messages;

if (!defined('ABSPATH')) exit;


class Common {

	/**
     * Constructor.
     */
    public function __construct() {

        add_action( 'save_post_at_biz_dir', [ $this, 'save_country_expert_field' ], 100, 3 );
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

    public function save_country_expert_field( $post_id, $post, $update ){
        update_option( 'save_country_expert_field', $post_id );
    }
}