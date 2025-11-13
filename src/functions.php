<?php
/**
 * Display any type of data in a readable format.
 *
 * @param mixed $data The data to display (array, object, string, number, etc.)
 */
function dns_display_data($data) {
    echo '<div class="dns-notification" style="padding:10px; border:1px solid #ccc; margin:10px 0;">';

    if (is_object($data)) {
        // Convert object to array recursively, then print
        echo '<pre>' . print_r(json_decode(json_encode($data), true), true) . '</pre>';
    } elseif (is_array($data)) {
        echo '<pre>' . print_r($data, true) . '</pre>';
    } elseif (is_bool($data)) {
        echo '<strong>Boolean:</strong> ' . ($data ? 'true' : 'false');
    } elseif (is_null($data)) {
        echo '<strong>NULL</strong>';
    } else {
        echo esc_html((string) $data);
    }

    echo '</div>';
}



/**
 * Get all terms where a post ID is in the 'subscribed_users' meta
 *
 * @param int $post_id The post ID to check
 * @param string $taxonomy Optional taxonomy to limit search
 * @return array Array of term objects
 */
function dns_get_terms_by_post_id($post_id, $taxonomy = '') {
    global $wpdb;

    // Base query to get all termmeta with key 'subscribed_users'
    $query = "SELECT term_id, meta_value
              FROM {$wpdb->termmeta}
              WHERE meta_key = 'subscribed_users'";

    // Limit to a taxonomy if provided
    if ($taxonomy) {
        $term_ids = get_terms([
            'taxonomy'   => $taxonomy,
            'fields'     => 'ids',
            'hide_empty' => false,
        ]);

        if (empty($term_ids)) return [];
        $term_ids = implode(',', array_map('intval', $term_ids));
        $query .= " AND term_id IN ($term_ids)";
    }

    $results = $wpdb->get_results( $query);

    $matched_terms = [];

    // Loop through results and check if $post_id exists in the serialized array
    foreach ($results as $row) {
        $term_id = (int) $row->term_id;
        $subscribed = get_term_meta( $term_id, 'subscribed_users', true);

        // return is_array( $subscribed );

        // Ensure it is always an array
        if ( ! is_array($subscribed)) {
            $subscribed = [];
        }
        
        return $subscribed;
    }

}

