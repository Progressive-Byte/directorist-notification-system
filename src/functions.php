<?php

/**
 * Display any type of data in a readable format.
 */
if ( ! function_exists( 'dns_display_data' ) ) {
    function dns_display_data($data) {
        echo '<div class="dns-notification" style="padding:10px; border:1px solid #ccc; margin:10px 0;">';

        if (is_object($data)) {
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
}





/**
 * Get all subscription data for a given post.
 */
if ( ! function_exists( 'dns_get_post_data' ) ) {
    function dns_get_post_data( $post_id ) {

        if ( ! $post_id || ! get_post( $post_id ) ) {
            return [];
        }

        $data = [];
        $data['post_title'] = get_the_title( $post_id );

        $subscribed_users = get_post_meta( $post_id, 'subscribed_users', true );
        $data['subscribed_users'] = is_array( $subscribed_users ) ? $subscribed_users : [];

        $taxonomies = [ 'atbdp_listing_types', 'at_biz_dir-location' ];
        $data['terms'] = [];

        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_post_terms( $post_id, $taxonomy );

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $data['terms'][ $taxonomy ] = [];

                foreach ( $terms as $term ) {
                    $term_users = get_term_meta( $term->term_id, 'subscribed_users', true );
                    $term_users = is_array( $term_users ) ? $term_users : [];

                    $data['terms'][ $taxonomy ]['subscribed_users'] = $term_users;
                }
            }
        }
        return $data;
    }
}


/**
 * Get all terms data including subscribed users.
 */
if ( ! function_exists( 'dns_get_terms_data' ) ) {
    function dns_get_terms_data( $taxonomies = [] ) {
        if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) return [];

        $data = [];

        foreach ( $taxonomies as $taxonomy ) {
            if ( ! taxonomy_exists( $taxonomy ) ) continue;

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                $data[$taxonomy] = [];
                continue;
            }

            foreach ( $terms as $term ) {
                $term_users = get_term_meta( $term->term_id, 'subscribed_users', true );
                $term_users = is_array( $term_users ) ? $term_users : [];

                $data[$taxonomy][] = [
                    'term_id'          => $term->term_id,
                    'name'             => $term->name,
                    'slug'             => $term->slug,
                    'subscribed_users' => $term_users,
                ];
            }
        }

        return $data;
    }
}


/**
 * Get all terms with at least one subscribed user.
 */
if ( ! function_exists( 'dns_get_terms_with_subscribers' ) ) {
    function dns_get_terms_with_subscribers( $taxonomies = [] ) {
        if ( empty( $taxonomies ) ) {
            return [];
        }

        $data = [];

        foreach ( $taxonomies as $taxonomy ) {

            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ]);

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            $all_users = [];

            // Only include meta keys that actually store user IDs
            $meta_keys = [
                'subscribed_users',
                'listing_types',
                'market_types',
            ]; 

            foreach ( $terms as $term ) {
                foreach ( $meta_keys as $meta ) {
                    $users = get_term_meta( $term->term_id, $meta, true );
                    if ( is_array( $users ) && ! empty( $users ) ) {
                        $all_users = array_merge( $all_users, $users );
                    }
                }
            }


            if ( ! empty( $all_users ) ) {
                $data[ $taxonomy ]['subscribed_users'] = array_unique( $all_users );
            }
        }

        return $data;
    }
}



/**
 * Extract only user IDs from taxonomy data.
 */
if ( ! function_exists( 'dns_extract_user_ids_from_taxonomy_data' ) ) {
    function dns_extract_user_ids_from_taxonomy_data( $taxonomy_data ) {
        $user_ids = [];

        foreach ( $taxonomy_data as $taxonomy => $data ) {
            if ( ! empty( $data['subscribed_users'] ) ) {
                $user_ids = array_merge( $user_ids, $data['subscribed_users'] );
            }
        }

        return array_unique( $user_ids );
    }
}


/**
 * Unsubscribe user completely from all term subscriptions
 *
 * @param int $user_id
 * @return bool
 */
function dns_unsubscribe_user( $user_id ) {

    if ( ! $user_id || ! is_numeric( $user_id ) ) {
        return false;
    }

    // Get all user preferences (listing_types, market_types, listing_locations)
    $prefs = get_user_meta( $user_id, 'dns_notify_prefs', true );

    if ( ! is_array( $prefs ) || empty( $prefs ) ) {
        return false;
    }

    // Loop through each group and remove user from subscribed term meta
    foreach ( $prefs as $group_key => $term_ids ) {

        if ( empty( $term_ids ) || ! is_array( $term_ids ) ) {
            continue;
        }

        foreach ( $term_ids as $term_id ) {

            $subscribed = get_term_meta( $term_id, 'subscribed_users', true );

            if ( is_array( $subscribed ) && in_array( $user_id, $subscribed, true ) ) {

                // Remove user ID
                $subscribed = array_diff( $subscribed, [ $user_id ] );

                // Update term meta
                update_term_meta( $term_id, 'subscribed_users', $subscribed );
            }
        }
    }

    // Remove all user notification meta
    delete_user_meta( $user_id, 'dns_notify_prefs' );
    delete_user_meta( $user_id, 'dns_email_subject' );
    delete_user_meta( $user_id, 'dns_email_body' );

    return true;
}






/**
 * Send BuddyBoss + Push Notification to a single user for a listing.
 *
 * @param int $user_id    ID of the user to notify.
 * @param int $listing_id ID of the listing post.
 */
if ( ! function_exists( 'dns_send_listing_notification' ) ) {
    function dns_send_listing_notification( $user_id, $listing_id ) {


        $user_id    = (int) $user_id;
        $listing_id = (int) $listing_id;

        if ( ! $user_id || ! $listing_id ) return false;

        $listing_title = get_the_title( $listing_id );
        $listing_link  = get_permalink( $listing_id );

        // --- BuddyBoss Notification ---
        if ( function_exists( 'bp_notifications_add_notification' ) ) {

            $notification_id = bp_notifications_add_notification( [
                'user_id'           => $user_id,
                'item_id'           => $listing_id,
                'secondary_item_id' => 0,
                'component_name'    => 'dns_matches',
                'component_action'  => 'new_listing_match',
                'is_new'            => 1,
                'allow_duplicate'   => false,
            ] );

            if ( $notification_id ) {
                bp_notifications_update_meta( $notification_id, 'message', "New Listing Match Found: $listing_title" );
                bp_notifications_update_meta( $notification_id, 'link', $listing_link );
            }
        }

        // --- Push Notification ---
        if ( function_exists( 'bp_push_notification_send' ) ) {
            bp_push_notification_send( [
                'user_id' => $user_id,
                'title'   => 'New Listing Match Found!',
                'message' => "A new listing matches your preferences: $listing_title",
                'url'     => $listing_link,
            ] );
        }

        return true;
    }
}

/* ============================================================
 * ✅ Template Loader
 * ============================================================ */

if ( ! function_exists( 'dns_load_template' ) ) {
    /**
     * Load a PHP template file and pass data to it.
     */
    function dns_load_template( $file, $args = [], $echo = true ) {
        if ( ! file_exists( $file ) ) return;

        if ( is_array( $args ) && ! empty( $args ) ) {
            extract( $args, EXTR_SKIP );
        }

        ob_start();
        include $file;
        $output = ob_get_clean();

        if ( $echo ) {
            echo $output;
            return null;
        }

        return $output;
    }
}

if ( ! function_exists( 'dns_get_cached_users' ) ) {
    /**
     * Get cached user list.
     *
     * @return array WP_User[]
     */
    function dns_get_cached_users() {
        $users = get_transient('dns_cached_users');

        if ( false === $users ) {
            // Query all users (or limit if huge number)
            $users = get_users([
                'orderby' => 'display_name',
                'order'   => 'ASC',
                'role'    => 'membre'
            ]);

            // Cache for 12 hours
            set_transient('dns_cached_users', $users, 12 * HOUR_IN_SECONDS);
        }

        return $users;
    }
}

if ( ! function_exists( 'dns_get_cached_pages' ) ) {
    /**
     * Get cached page list.
     *
     * @return array WP_Post[]
     */
    function dns_get_cached_pages() {
        $pages = get_transient('dns_cached_pages');

        if ( false === $pages ) {
            $pages = get_pages([
                'sort_column' => 'post_title',
                'sort_order'  => 'ASC',
            ]);

            // Cache for 12 hours
            set_transient('dns_cached_pages', $pages, 12 * HOUR_IN_SECONDS);
        }

        return $pages;
    }
}

/**
 * Get all term objects for a directory type ID.
 *
 * @param int $type_id The directory type ID (e.g., 357)
 * @return array List of WP_Term objects
 */
function get_all_terms_by_directory_type( $type_id ) {
    global $wpdb;

    if ( ! $type_id ) {
        return [];
    }

    $meta_key = '_directory_type_' . intval( $type_id );

    // Get all term IDs from wp_termmeta
    $term_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value = 1",
        $meta_key
    ) );

    if ( empty( $term_ids ) ) {
        return [];
    }

    $terms = [];
    foreach ( $term_ids as $term_id ) {
        $term = get_term( $term_id ); // Get full WP_Term object
        if ( $term && ! is_wp_error( $term ) ) {
            $terms[] = $term;
        }
    }

    return $terms;
}

/**
 * Update term subscribed users for the current user.
 *
 * @param array $term_ids Array of term IDs to subscribe the current user to.
 */
// function update_user_term_subscriptions( $term_ids = [], $meta_key = 'subscribed_users' ) {

//     if ( empty( $term_ids ) ) {
//         return;
//     }

//     // Convert objects to IDs automatically
//     $term_ids = array_map( function($t) {
//         return is_object($t) ? intval($t->term_id) : intval($t);
//     }, $term_ids );

//     $current_user_id = get_current_user_id();
//     if ( ! $current_user_id ) {
//         return;
//     }


//     foreach ( $term_ids as $term_id ) {

//         if ( $term_id <= 0 ) {
//             continue;
//         }

//         $subscribed_users = get_term_meta( $term_id, $meta_key, true );
//         $subscribed_users = is_array( $subscribed_users ) ? $subscribed_users : [];

//         if ( ! in_array( $current_user_id, $subscribed_users, true ) ) {
//             $subscribed_users[] = $current_user_id;
//             update_option( 'subscribed_users_'. $term_id , $subscribed_users );
//             update_term_meta( $term_id, $meta_key, $subscribed_users );
//         }
//     }
// }



function get_taxonomy_by_term_id( $term_id ) {
    $term = get_term( $term_id );

    if ( ! $term || is_wp_error( $term ) ) {
        return false; // Term not found
    }

    return $term->taxonomy; // Returns taxonomy name as string
}

/**
 * Retrieve selected directories for all categories.
 *
 * Returns an associative array where:
 *      key   = category term ID
 *      value = selected directory ID (single value)
 *
 * @return array Array of term_id => directory_id.
 */
function dns_get_selected_directories_for_categories() {

    $selected_directories = array();

    // Get all terms from ATBDP category taxonomy.
    $terms = get_terms(
        array(
            'taxonomy'   => ATBDP_CATEGORY,
            'hide_empty' => false,
            'orderby'    => 'date',
            'order'      => 'DESC',
        )
    );

    // Validate result before processing.
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array();
    }

    foreach ( $terms as $term ) {

        // Get directory ID array assigned to this term (example: array( 355 ) ).
        $dirs = directorist_get_category_directory( $term->term_id );

        // Convert directory array to single directory value.
        $selected_directories[ $term->term_id ] = ( is_array( $dirs ) && ! empty( $dirs ) )
            ? $dirs[0]
            : null;
    }

    return $selected_directories;
}

/**
 * Group all terms by their selected directory.
 *
 * Converts:
 *      [ term_id => directory_id ]
 * Into:
 *      [ directory_id => [ term_id, term_id... ] ]
 *
 * @param array $selected_directories Mapping of term_id => directory_id.
 * @return array                       Grouped directory_id => [ term_ids ].
 */
function dns_group_terms_by_directory( $selected_directories ) {

    $grouped = array();

    if ( empty( $selected_directories ) || ! is_array( $selected_directories ) ) {
        return array();
    }

    foreach ( $selected_directories as $term_id => $directory_id ) {

        // Initialize array for each directory group.
        if ( ! isset( $grouped[ $directory_id ] ) ) {
            $grouped[ $directory_id ] = array();
        }

        // Append the term ID to that directory group.
        $grouped[ $directory_id ][] = $term_id;
    }

    return $grouped;
}

/**
 * Return all term IDs assigned to a given directory ID.
 *
 * Example:
 *      dns_get_terms_by_directory( 355 );
 *
 * @param int|string $directory_id The directory ID to search for.
 * @return array                    List of term IDs under that directory.
 */
function dns_get_terms_by_directory( $directory_id ) {

    // Step 1: Get mapping: term_id => directory_id.
    $selected_directories = dns_get_selected_directories_for_categories();

    // Step 2: Group by directory: directory_id => array( term_ids )
    $grouped = dns_group_terms_by_directory( $selected_directories );

    // Step 3: Return the terms for this directory or an empty array.
    return isset( $grouped[ $directory_id ] )
        ? $grouped[ $directory_id ]
        : array();
}

/**
 * Get full WP_Term objects for all terms under a directory.
 *
 * @param int|string $directory_id Directory ID.
 * @return array Array of WP_Term objects.
 */
function dns_get_term_objects_by_directory( $directory_id ) {

    // Get only term IDs first.
    $term_ids = dns_get_terms_by_directory( $directory_id );

    if ( empty( $term_ids ) ) {
        return array();
    }

    // Get full term objects.
    $terms = get_terms(
        array(
            'taxonomy'   => ATBDP_CATEGORY,
            'hide_empty' => false,
            'include'    => $term_ids,
        )
    );

    return ! is_wp_error( $terms ) ? $terms : array();
}

/**
 * Get all user IDs subscribed to a post via term meta.
 *
 * @param int   $post_id  The post ID.
 * @param array $taxonomies Optional. List of taxonomies to check. Default: all taxonomies of the post type.
 *
 * @return array Unique user IDs
 */
function dns_get_subscribed_users_by_post( $post_id, $taxonomies = [] ) {

    if ( empty( $taxonomies ) ) {
        // Get all taxonomies for this post type
        $taxonomies = get_object_taxonomies( get_post_type( $post_id ), 'names' );
    }

    $user_ids = [];

    foreach ( $taxonomies as $taxonomy ) {
        $terms = wp_get_post_terms( $post_id, $taxonomy );

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $subscribed = get_term_meta( $term->term_id, 'subscribed_users', true );

                if ( is_array( $subscribed ) && ! empty( $subscribed ) ) {
                    $user_ids = array_merge( $user_ids, $subscribed );
                }
            }
        }
    }

    // Remove duplicate user IDs
    $user_ids = array_unique( $user_ids );

    return $user_ids;
}

/**
 * Add a user ID to term meta `subscribed_users`.
 *
 * @param array|int $term_ids Term ID or array of term IDs
 * @param int       $user_id  User ID
 */
function dns_add_user_to_term( $term_ids, $user_id ) {
    if ( ! is_array( $term_ids ) ) {
        $term_ids = [ $term_ids ];
    }

    foreach ( $term_ids as $term_id ) {
        $existing = get_term_meta( $term_id, 'subscribed_users', true );
        if ( ! is_array( $existing ) ) {
            $existing = [];
        }

        if ( ! in_array( $user_id, $existing, true ) ) {
            $existing[] = $user_id;
            update_term_meta( $term_id, 'subscribed_users', $existing );
        }
    }
}

/**
 * Remove a user ID from term meta `subscribed_users`.
 *
 * @param array|int $term_ids Term ID or array of term IDs
 * @param int       $user_id  User ID
 */
function remove_user_from_terms( $term_ids, $user_id ) {
    if ( empty( $term_ids ) ) {
        return;
    }

    if ( ! is_array( $term_ids ) ) {
        $term_ids = [ $term_ids ];
    }

    foreach ( $term_ids as $term_id ) {
        $existing = get_term_meta( $term_id, 'subscribed_users', true );

        if ( ! is_array( $existing ) || empty( $existing ) ) {
            continue;
        }

        if ( in_array( $user_id, $existing, true ) ) {
            $existing = array_diff( $existing, [ $user_id ] );
            update_term_meta( $term_id, 'subscribed_users', $existing );
        }
    }
}
