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
	    $current_page = get_the_ID();

	    // Exit if no subscription page selected
	    if ( empty( $subscription_id ) ) {
	        return;
	    }

	    // Show button on Product Listing page
	    if ( $show_product && $current_page === $product_page ) {
	        echo '<a href="' . esc_url( get_permalink( $subscription_id ) ) . '" class="dns-subscribe-button">Subscribe <br> to Notifications</a>';
	    }

	    // Show button on Job Listing page
	    if ( $show_job && $current_page === $job_page ) {
	        echo '<a href="' . esc_url( get_permalink( $subscription_id ) ) . '" class="dns-subscribe-button">Subscribe <br> to Notifications</a>';
	    }
	}



}
