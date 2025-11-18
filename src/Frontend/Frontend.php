<?php
namespace DNS\Frontend;
use DNS\Helper\Messages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class for DNS
 */
class Frontend {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'add_subscribe_button' ] );
		add_action( 'wp_head', [ $this, 'head' ] );
	}

	public function head(){
		$args = array(
		    'post_type'      => ATBDP_POST_TYPE,  // at_biz_dir
		    'posts_per_page' => -1,               // all posts
		    'post_status'    => 'publish',
		    'tax_query'      => array(
		        array(
		            'taxonomy' => ATBDP_DIRECTORY_TYPE, // 'directory_type'
		            'field'    => 'term_id',
		            'terms'    => 316,
		        ),
		    ),
		);


		$query = new \WP_Query($args);

		$directory_types = get_terms( array(
		    'taxonomy'   => ATBDP_DIRECTORY_TYPE,
		    'hide_empty' => false, // set true if you only want types assigned to posts
		) );

		$term_ids = wp_list_pluck( $directory_types, 'term_id' );

		// Messages::pri( $term_ids );


	}
	
	/**
	 * Display the floating "Subscribe to Notifications" button on selected pages.
	 */
	public function add_subscribe_button() {

	    // Get admin settings
	    $show_job        = get_option( 'dns_subscribe_job' );
	    $show_product    = get_option( 'dns_subscribe_product' );
	    $job_page        = (int) get_option( 'dns_subscription_page_job' );
	    $product_page    = (int) get_option( 'dns_subscription_page_product' );
	    $subscription_id = get_option( 'dns_subscription_page_id' );

	    // Get current page ID
	    $current_page_id = get_the_ID();

	    // Exit if no subscription page selected
	    if ( empty( $subscription_id ) ) {
	        return;
	    }

	    // Show button on Product Listing page
	    if ( $show_product && $current_page_id === $product_page ) {
	        echo '<a href="' . esc_url( get_permalink( $subscription_id ) ) . '" class="dns-subscribe-button">Subscribe <br> to Notifications</a>';
	    }

	    // Show button on Job Listing page
	    if ( $show_job && $current_page_id === $job_page ) {
	        echo '<a href="' . esc_url( get_permalink( $subscription_id ) ) . '" class="dns-subscribe-button">Subscribe <br> to Notifications</a>';
	    }
	}



}
