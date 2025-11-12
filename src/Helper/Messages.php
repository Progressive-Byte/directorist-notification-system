<?php

namespace DNS\Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

class Messages {

    /**
     * Display messages in styled boxes
     *
     * @param string|array $data Single message or array of messages
     */
    public static function pri( $data ) {

        $messages = is_array( $data ) ? $data : [ $data ];

        // Inline CSS (only once)
        static $css_loaded = false;
        if ( ! $css_loaded ) {
            $css_loaded = true;
            echo '<style>
                .dns-notification-box {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    border-radius: 8px;
                    padding: 14px 18px;
                    margin: 12px 0;
                    font-family: "Segoe UI", Roboto, sans-serif;
                    font-size: 15px;
                    line-height: 1.4;
                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
                    background-color: #17a2b8;
                    color: #fff;
                    animation: fadeIn 0.3s ease-in-out;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-5px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .dns-message { flex: 1; }
            </style>';
        }

        // Render each message
        foreach ( $messages as $msg ) {
            echo '<div class="dns-notification-box">';
            echo '<span class="dns-message">' . esc_html( $msg ) . '</span>';
            echo '</div>';
        }
    }
}
