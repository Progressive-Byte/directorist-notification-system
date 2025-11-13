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

    	$post_id = 678 ; // The post ID you want to check
		$taxonomy = 'atbdp_listing_types'; // Optional, limit to a taxonomy

		$terms = dns_get_terms_by_post_id( $post_id, $taxonomy );

		// Messages::pri( $terms );

		// foreach ($terms as $term) {
		//     Messages::pri( $term->name . ' (ID: ' . $term->term_id . ')<br>' );
		// }
    }

    public function save_country_expert_field( $post_id, $post, $update ){
        update_option( 'save_country_expert_field', $post_id );
    }
}