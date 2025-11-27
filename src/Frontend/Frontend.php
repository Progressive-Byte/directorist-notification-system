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
		// $selected_directories = dns_get_selected_directories_for_categories();

		// $grouped = dns_get_terms_by_directory( 355 );
		// Messages::pri( $grouped );

		// // Messages::pri($selected_directories);

		// Optional head scripts or styles
	}

	/**
	 * Display the floating "Subscribe to Notifications" button on selected pages.
	 */
	public function add_subscribe_button() {

		// Get current page ID
		$current_page_id = get_queried_object_id();

		// Get saved subscription pages from settings

		$subscription_page_id   = get_option('dns_subscription_page_id');

		$subscription_pages = get_option( 'dns_subscription_pages', [] );
		$subscription_pages = is_array( $subscription_pages ) ? array_map( 'intval', $subscription_pages ) : [];

		// Check if current page is in the subscription pages
		if ( in_array( $current_page_id, $subscription_pages, true ) ) :
			?>
		    <a href="<?php echo esc_url( get_permalink( $subscription_page_id ) ); ?>" class="dns-subscribe-button">
		        <?php esc_html_e( 'Subscribe', 'directorist-notification-system' ); ?>
		    </a>
		<?php
		endif;
	}
}
