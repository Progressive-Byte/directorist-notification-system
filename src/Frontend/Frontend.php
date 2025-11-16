<?php
namespace DNS\Frontend;

use DNS\Helper\Messages;

class Frontend {

    public function __construct() {
        add_action('wp_head', [ $this, 'head'] );
        add_action('wp_footer', [$this, 'add_subscribe_button']);
        
    }

    public function head(){  

     

        // $user_id = get_current_user_id();
        // $listing_id = 11180647;
		// Messages::pri( get_current_user_id() );

        // dns_notify_new_listing_match( $user_id, $listing_id );
      

    }

    public function add_subscribe_button() {
	    // Check if on Job Listing or Product Listing page
	    if ( is_singular('at_biz_dir') || is_post_type_archive('at_biz_dir') ) {

	        // Check settings
	        $show_job      = get_option('dns_subscribe_job');
	        $show_product  = get_option('dns_subscribe_product');
	        $job_page      = get_option('dns_subscription_page_job');
	        $product_page  = get_option('dns_subscription_page_product');

	        $url = '';

	        // Decide which URL to use based on page type
	        if ( is_singular('at_biz_dir') && $show_job && $job_page ) {
	            $url = get_permalink($job_page);
	        } elseif ( is_post_type_archive('at_biz_dir') && $show_product && $product_page ) {
	            $url = get_permalink($product_page);
	        }

	        if ( $url ) {
	            echo '<a href="' . esc_url($url) . '" class="dns-subscribe-button" target="_blank">Subscribe to Notifications</a>';
	        }
	    }
	}



}
