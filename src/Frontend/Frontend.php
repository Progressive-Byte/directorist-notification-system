<?php
namespace DNS\Frontend;

use DNS\Helper\Messages;

class Frontend {

    public function __construct() {
        add_action('wp_head', [ $this, 'head'] );
    }

    public function head(){       

        // if ( ! empty( $listings )){
        //     Messages::pri( $listings );
        // }

    }
}
