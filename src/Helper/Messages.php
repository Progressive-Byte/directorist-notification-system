<?php

namespace DNS\Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

class Messages {

    /**
     * Display messages in styled boxes
     *
     * @param string|array $data Single message or array of messages
     */
    public static function pri( $data, $admin_only = true, $hide_adminbar = true ) {
        if ( $admin_only && ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<pre>';
        if ( is_object( $data ) || is_array( $data ) ) {
            print_r( $data );
        } else {
            var_dump( $data );
        }
        echo '</pre>';

        if ( is_admin() && $hide_adminbar ) {
            echo '<style>#adminmenumain{display:none;}</style>';
        }
    }
}
