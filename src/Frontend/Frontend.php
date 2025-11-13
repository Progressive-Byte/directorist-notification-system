<?php
namespace DNS\Frontend;

use DNS\Helper\Messages;

class Frontend {

    public function __construct() {
        // add_action('wp_footer', [ $this, 'show_notification'] );
        add_action('wp_head', [ $this, 'head'] );
    }

    public function head(){
    }

    public function show_notification() {
        echo '<div id="dns-notification" style="position: fixed; bottom: 20px; right: 20px; background: #0073aa; color: #fff; padding: 15px; border-radius: 5px; display:none;">
                This is a directory notification!
              </div>';
        ?>
        <script>
        jQuery(document).ready(function($){
            $('#dns-notification').fadeIn().delay(3000).fadeOut();
        });
        </script>
        <?php
    }
}
