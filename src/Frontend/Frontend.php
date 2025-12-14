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

	public function __construct() {
		add_action( 'wp_footer', [ $this, 'add_subscribe_button' ] );
		add_action( 'wp_head', [ $this, 'head' ] );
	}

	public function head(){
		

		// // Messages::pri($selected_directories);

		// Optional head scripts or styles
	}

	/**
	 * Display the floating "Subscribe to Notifications" button on selected pages.
	 */
	public function add_subscribe_button() {

	    // Get current page ID
	    $current_page_id = get_queried_object_id();

	    // Get main and secondary subscription pages
	    $market_page_id = get_option('dns_subscription_page_id');  // Main / Market Page
	    $job_page_id    = get_option('dns_secondary_page_id');     // Secondary / Job Page

	    // Get all pages enabled for subscribe button
	    $subscription_pages = get_option('dns_subscription_pages', []);
	    $subscription_pages = is_array($subscription_pages) ? array_map('intval', $subscription_pages) : [];

	    // Only show buttons on subscription pages
	    if ( in_array( $current_page_id, $subscription_pages, true ) ) : ?>
	        
	        <?php if ( $market_page_id ) : ?>
	            <a href="<?php echo esc_url( get_permalink( $market_page_id ) ); ?>" class="dns-subscribe-button">
	                <?php esc_html_e( 'Market', 'directorist-notification-system' ); ?>
	            </a>
	        <?php endif; ?>

	        <?php if ( $job_page_id ) : ?>
	            <a href="<?php echo esc_url( get_permalink( $job_page_id ) ); ?>" class="dns-subscribe-button dns-job-button">
	                <?php esc_html_e( 'Job List', 'directorist-notification-system' ); ?>
	            </a>
	        <?php endif; ?>

	    <?php
	    endif;
	}

}
