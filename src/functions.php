<?php
/**
 * Display any type of data in a readable format.
 *
 * @param mixed $data The data to display (array, object, string, number, etc.)
 */
function dns_display_data($data) {
    echo '<div class="dns-notification" style="padding:10px; border:1px solid #ccc; margin:10px 0;">';

    if (is_array($data) || is_object($data)) {
        echo '<pre>' . print_r($data, true) . '</pre>';
    } elseif (is_bool($data)) {
        echo '<strong>Boolean:</strong> ' . ($data ? 'true' : 'false');
    } elseif (is_null($data)) {
        echo '<strong>NULL</strong>';
    } else {
        echo esc_html($data);
    }

    echo '</div>';
}