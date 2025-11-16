<?php
namespace DNS\Frontend;

use DNS\Helper\Messages;

class Frontend {

    public function __construct() {
        add_action('wp_head', [ $this, 'head'] );
        
    }

    public function head(){  

     

        // $user_id = get_current_user_id();
        // $listing_id = 11180647;
		// Messages::pri( get_current_user_id() );

        // dns_notify_new_listing_match( $user_id, $listing_id );
      

    }


}
