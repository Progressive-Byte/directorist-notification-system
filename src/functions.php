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
function dns_get_terms_by_post_id( $post_id, $taxonomy = '' ) {
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

        // Ensure it is always an array
        if ( ! is_array($subscribed)) {
            $subscribed = [];
        }
        
        return $subscribed;
    }

}

/**
 * Get all subscription data for a given post.
 *
 * @param int $post_id The post ID to fetch data for.
 * @return array Data including post meta and term meta.
 */
function dns_get_post_data( $post_id ) {

    if ( ! $post_id || ! get_post( $post_id ) ) {
        return []; // Invalid post
    }

    $data = [];

    // Get post title
    $data['post_title'] = get_the_title( $post_id );

    // Get post meta: subscribed users
    $subscribed_users = get_post_meta( $post_id, 'subscribed_users', true );
    $data['subscribed_users'] = is_array( $subscribed_users ) ? $subscribed_users : [];

    // Get all taxonomy terms for this post
    $taxonomies = [ 'atbdp_listing_types', 'at_biz_dir-location' ]; // add more if needed
    $data['terms'] = [];

    foreach ( $taxonomies as $taxonomy ) {
        $terms = wp_get_post_terms( $post_id, $taxonomy );

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $data['terms'][ $taxonomy ] = [];

            foreach ( $terms as $term ) {
               

                // Get users subscribed to this term
                $term_users = get_term_meta( $term->term_id, 'subscribed_users', true );
                $term_data['subscribed_users'] = is_array( $term_users ) ? $term_users : [];

                $data['terms'][ $taxonomy ]['subscribed_users'] = $term_users;
            }
        }
    }

    return $data;
}

/**
 * Get all terms data for given taxonomies including subscribed users.
 *
 * @param array $taxonomies Array of taxonomy slugs.
 * @return array Data for each taxonomy including term info and subscribed users.
 */
function dns_get_terms_data( $taxonomies = [] ) {

    if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
        return [];
    }

    $data = [];

    foreach ( $taxonomies as $taxonomy ) {

        if ( ! taxonomy_exists( $taxonomy ) ) {
            continue;
        }

        $terms = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            $data[ $taxonomy ] = [];
            continue;
        }

        $data[ $taxonomy ] = [];

        foreach ( $terms as $term ) {

            $term_users = get_term_meta( $term->term_id, 'subscribed_users', true );
            $term_users = is_array( $term_users ) ? $term_users : [];

            $data[ $taxonomy ][] = [
                'term_id'           => $term->term_id,
                'name'              => $term->name,
                'slug'              => $term->slug,
                'subscribed_users'  => $term_users,
            ];
        }
    }

    return $data;
}

    /**
     * Get all terms with at least one subscribed user.
     *
     * @param array $taxonomies Array of taxonomy slugs.
     * @return array Filtered terms data with non-empty subscribed_users.
     */
    function dns_get_terms_with_subscribers( $taxonomies = [] ) {

        if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
            return [];
        }

        $data = [];

        foreach ( $taxonomies as $taxonomy ) {

            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }

            $terms = get_terms(
                [
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ]
            );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            $all_users = [];

            foreach ( $terms as $term ) {
                $subscribed_users = get_term_meta( $term->term_id, 'subscribed_users', true );
                $subscribed_users = is_array( $subscribed_users ) ? $subscribed_users : [];

                if ( ! empty( $subscribed_users ) ) {
                    $all_users = array_merge( $all_users, $subscribed_users );
                }
            }

            // Only include taxonomy if there are subscribed users
            if ( ! empty( $all_users ) ) {
                $data[ $taxonomy ]['subscribed_users'] = array_unique( $all_users );
            }
        }

        return $data;
    }

function dns_extract_user_ids_from_taxonomy_data( $taxonomy_data ) {
    $user_ids = [];

    if ( empty( $taxonomy_data ) ) {
        return $user_ids;
    }

    foreach ( $taxonomy_data as $taxonomy => $data ) {
        if ( ! empty( $data['subscribed_users'] ) ) {
            $user_ids = array_merge( $user_ids, $data['subscribed_users'] );
        }
    }

    return array_unique( $user_ids );
}

