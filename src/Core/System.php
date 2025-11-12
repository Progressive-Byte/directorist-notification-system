<?php
namespace DNS\Core;

class System {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        new \DNS\Admin\NotificationAdmin();
        new \DNS\Frontend\NotificationFrontend();
    }
}
