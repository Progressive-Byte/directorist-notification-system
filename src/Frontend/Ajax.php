<?php
namespace DNS\Frontend;

use DNS\Helper\Messages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ajax {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'head' ] );
        // add_action( 'wp_ajax_dns_get_locations', [ $this, 'ajax_get_locations' ] );
    }

    function head(){
        // Messages::pri( 'Ajax' );
    }

    public function ajax_get_locations() {

        check_ajax_referer( 'dns_loc_nonce', 'nonce' );

        $parent_id = absint( $_POST['parent_id'] ?? 0 );

        if ( $parent_id ) {

            $terms = get_terms([
                'taxonomy'   => 'at_biz_dir-location',
                'hide_empty' => false,
                'parent'     => $parent_id,
            ]);

            // If parent has no children → show all
            if ( empty( $terms ) ) {
                $terms = get_terms([
                    'taxonomy'   => 'at_biz_dir-location',
                    'hide_empty' => false,
                ]);
            }

        } else {
            // No parent selected → all locations
            $terms = get_terms([
                'taxonomy'   => 'at_biz_dir-location',
                'hide_empty' => false,
            ]);
        }

        $response = [];

        foreach ( $terms as $term ) {
            $response[] = [
                'id'   => $term->term_id,
                'name' => $term->name,
            ];
        }

        wp_send_json_success( $response );
    }

}
