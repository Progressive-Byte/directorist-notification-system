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
 */
if ( ! function_exists( 'dns_unsubscribe_user' ) ) {
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

        return true;
    }
}

/**
 * Send BuddyBoss + Push Notification to a single user for a listing.
 */
if ( ! function_exists( 'dns_send_listing_notification' ) ) {

    function dns_send_listing_notification( $user_id, $listing_id ) {

        $user_id    = (int) $user_id;
        $listing_id = (int) $listing_id;

        if ( ! $user_id || ! $listing_id ) {
            return false;
        }

        // User
        $user      = get_user_by( 'id', $user_id );
        $user_name = $user ? $user->display_name : __( 'there', 'dns' );

        // Listing
        $listing_title = get_the_title( $listing_id );
        $listing_link  = get_permalink( $listing_id );

        // Locations
        $locations = get_the_terms( $listing_id, 'at_biz_dir-location' );
        $location_names = [];

        if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) {
            $location_names = wp_list_pluck( $locations, 'name' );
        }

        $location_text = ! empty( $location_names )
            ? implode( ', ', $location_names )
            : __( 'your preferred location', 'dns' );

        // Categories (Job / Marketplace etc.)
        $categories = get_the_terms( $listing_id, 'at_biz_dir-category' );
        $category_name = ! empty( $categories ) && ! is_wp_error( $categories )
            ? $categories[0]->name
            : __( 'listing', 'dns' );

        // Unsubscribe URL
        $unsubscribe_url = dns_get_unsubscribe_url( $user_id );

        // Message
        $message = sprintf(
            __( 'Hi %1$s, a new %2$s listing matched your location (%3$s). View it here: %4$s. If you want to unsubscribe, click here: %5$s', 'dns' ),
            $user_name,
            $category_name,
            $location_text,
            $listing_link,
            $unsubscribe_url
        );

        /* BuddyBoss / BuddyPress Notification */
        if ( function_exists( 'bp_notifications_add_notification' ) ) {

            $notification_id = bp_notifications_add_notification( [
                'user_id'           => $user_id,
                'item_id'           => $listing_id,
                'component_name'    => 'activity',
                'component_action'  => 'dns_new_listing_match',
                'date_notified'     => bp_core_current_time(),
                'is_new'            => 1,
                'allow_duplicate'   => false,
            ] );

            if ( $notification_id ) {
                bp_notifications_update_meta( $notification_id, 'dns_message', $message );
                bp_notifications_update_meta( $notification_id, 'dns_link', $listing_link );
            }
        }

        /* BuddyBoss Push Notification */
        // if ( function_exists( 'bp_push_notification_send' ) ) {
        //     bp_push_notification_send( [
        //         'user_id' => $user_id,
        //         'title'   => __( 'New Listing Match Found!', 'dns' ),
        //         'message' => wp_strip_all_tags( $message ),
        //         'url'     => $listing_link,
        //     ] );
        // }

        return true;
    }
}


if ( ! function_exists( 'dns_get_unsubscribe_url' ) ) {

    /**
     * Generate unsubscribe URL for a user
     *
     * @param int $user_id
     * @return string|false
     */
    function dns_get_unsubscribe_url( $user_id ) {

        $user_id = (int) $user_id;

        if ( ! $user_id ) {
            return false;
        }

        return add_query_arg(
            [
                'dns_unsubscribe' => 1,
                'user_id'         => $user_id,
                'nonce'           => wp_create_nonce( 'dns_unsubscribe_' . $user_id ),
            ],
            home_url( '/' )
        );
    }
}



/**
 * Load a PHP template file and pass data to it.
 */
if ( ! function_exists( 'dns_load_template' ) ) {
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


/**
 * Get cached user list.
 */
if ( ! function_exists( 'dns_get_cached_users' ) ) {
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


/**
 * Get cached page list.
 */
if ( ! function_exists( 'dns_get_cached_pages' ) ) {
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
 */
if ( ! function_exists( 'get_all_terms_by_directory_type' ) ) {
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
}


/**
 * Get taxonomy by term ID.
 */
if ( ! function_exists( 'get_taxonomy_by_term_id' ) ) {
    function get_taxonomy_by_term_id( $term_id ) {
        $term = get_term( $term_id );

        if ( ! $term || is_wp_error( $term ) ) {
            return false; // Term not found
        }

        return $term->taxonomy; // Returns taxonomy name as string
    }
}


/**
 * Retrieve selected directories for all categories.
 */
if ( ! function_exists( 'dns_get_selected_directories_for_categories' ) ) {
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
}


/**
 * Group all terms by their selected directory.
 */
if ( ! function_exists( 'dns_group_terms_by_directory' ) ) {
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
}


/**
 * Return all term IDs assigned to a given directory ID.
 */
if ( ! function_exists( 'dns_get_terms_by_directory' ) ) {
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
}


/**
 * Get full WP_Term objects for all terms under a directory.
 */
if ( ! function_exists( 'dns_get_term_objects_by_directory' ) ) {
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
}


/**
 * Get all user IDs subscribed to a post via term meta or user preferences.
 */
if ( ! function_exists( 'dns_get_subscribed_users_by_post' ) ) {
    function dns_get_subscribed_users_by_post( $post_id, $taxonomies = [] ) {

        $user_ids = [];

        // Get post terms per taxonomy
        if ( empty( $taxonomies ) ) {
            $taxonomies = get_object_taxonomies( get_post_type( $post_id ), 'names' );
        }

        $post_terms = [];
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_post_terms( $post_id, $taxonomy, ['fields' => 'ids'] );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $post_terms[ $taxonomy ] = $terms;
            }
        }

        $post_locations = wp_get_post_terms( $post_id, ATBDP_LOCATION, ['fields' => 'ids'] );
        if ( is_wp_error( $post_locations ) || empty( $post_locations ) ) {
            $post_locations = [];
        }

        // Map post taxonomies to user meta keys
        $taxonomy_to_meta = [
            'atbdp_listing_types' => 'listing_types',
            'at_biz_dir-category' => 'market_types',
            // add more if needed
        ];

        // Get all users with notification prefs
        $users = get_users([
            'meta_key'     => 'dns_notify_prefs',
            'meta_compare' => 'EXISTS',
        ]);

        foreach ( $users as $user ) {
            $prefs = get_user_meta( $user->ID, 'dns_notify_prefs', true );
            if ( empty( $prefs ) || ! is_array( $prefs ) ) continue;

            $matched = false;

            foreach ( $taxonomy_to_meta as $taxonomy => $meta_key ) {
                if ( empty( $prefs[ $meta_key ]['listing'] ) || empty( $post_terms[ $taxonomy ] ) ) {
                    continue;
                }

                $listing_match = array_intersect( $prefs[ $meta_key ]['listing'], $post_terms[ $taxonomy ] );
                $location_match = ! empty( $prefs[ $meta_key ]['locations'] )
                    ? array_intersect( $prefs[ $meta_key ]['locations'], $post_locations )
                    : [];

                if ( ! empty( $listing_match ) && ! empty( $location_match ) ) {
                    $matched = true;
                    break; // stop loop if matched
                }
            }

            if ( $matched ) {
                $user_ids[] = $user->ID;
            }
        }

        return array_unique( $user_ids );
    }
}


/**
 * Add a user ID to term meta `subscribed_users`.
 */
if ( ! function_exists( 'dns_add_user_to_term' ) ) {
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
}


/**
 * Remove a user ID from term meta `subscribed_users`.
 */
if ( ! function_exists( 'remove_user_from_terms' ) ) {
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
}


/**
 * Find users whose meta data matches a post's categories and locations
 */
if ( ! function_exists( 'dns_find_matching_users_for_post' ) ) {
    function dns_find_matching_users_for_post( $post_id ) {

        // Get post categories and locations
        $post_categories = wp_get_post_terms( $post_id, 'at_biz_dir-category', ['fields' => 'ids'] );
        $post_locations = wp_get_post_terms( $post_id, 'at_biz_dir-location', ['fields' => 'ids'] );

        // Handle potential errors
        if ( is_wp_error( $post_categories ) ) {
            $post_categories = [];
        }

        if ( is_wp_error( $post_locations ) ) {
            $post_locations = [];
        }

        // If no categories or locations, return empty array
        if ( empty( $post_categories ) && empty( $post_locations ) ) {
            return [];
        }

        // Get all users who have notification preferences
        $users = get_users([
            'meta_key'     => 'dns_notify_prefs',
            'meta_compare' => 'EXISTS',
        ]);

        $matching_users = [];

        foreach ( $users as $user ) {
            $user_prefs = get_user_meta( $user->ID, 'dns_notify_prefs', true );

            if ( empty( $user_prefs ) || ! is_array( $user_prefs ) ) {
                continue;
            }

            // Check both listing_types and market_types
            foreach ( ['listing_types', 'market_types'] as $pref_type ) {

                if ( ! isset( $user_prefs[ $pref_type ] ) || ! is_array( $user_prefs[ $pref_type ] ) ) {
                    continue;
                }

                $user_listings = isset( $user_prefs[ $pref_type ]['listing'] ) ? $user_prefs[ $pref_type ]['listing'] : [];
                $user_locations = isset( $user_prefs[ $pref_type ]['locations'] ) ? $user_prefs[ $pref_type ]['locations'] : [];

                // Check if user has matching categories and locations
                $category_match = ! empty( array_intersect( $post_categories, $user_listings ) );
                $location_match = ! empty( array_intersect( $post_locations, $user_locations ) );

                if ( $category_match && $location_match ) {
                    $matching_users[] = $user->ID;
                    break; // Found a match, no need to check other pref types for this user
                }
            }
        }

        return array_unique( $matching_users );
    }
}


/**
 * Check if multiple address feature is enabled
 */
if ( ! function_exists( 'dns_is_multiple_address_enabled' ) ) {
    function dns_is_multiple_address_enabled() {
        return (bool) get_option('dns_multiple_address_enabled', false);
    }
}