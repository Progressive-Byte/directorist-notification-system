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
 * Get all terms where a post ID is in the 'subscribed_users' meta.
 */
if ( ! function_exists( 'dns_get_terms_by_post_id' ) ) {
    function dns_get_terms_by_post_id( $post_id, $taxonomy = '' ) {
        global $wpdb;

        $query = "SELECT term_id, meta_value
                  FROM {$wpdb->termmeta}
                  WHERE meta_key = 'subscribed_users'";

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

        $results = $wpdb->get_results( $query );

        foreach ($results as $row) {
            $subscribed = get_term_meta( (int) $row->term_id, 'subscribed_users', true );
            return is_array($subscribed) ? $subscribed : [];
        }
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
        if ( empty( $taxonomies ) ) return [];

        $data = [];

        foreach ( $taxonomies as $taxonomy ) {
            if ( ! taxonomy_exists( $taxonomy ) ) continue;

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ]);

            if ( is_wp_error( $terms ) || empty( $terms ) ) continue;

            $all_users = [];

            foreach ( $terms as $term ) {
                $users = get_term_meta( $term->term_id, 'subscribed_users', true );
                if ( is_array( $users ) && ! empty( $users ) ) {
                    $all_users = array_merge( $all_users, $users );
                }
            }

            if ( ! empty( $all_users ) ) {
                $data[$taxonomy]['subscribed_users'] = array_unique( $all_users );
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
 * Remove a user from all post + term subscriptions.
 */
if ( ! function_exists( 'dns_remove_user_from_subscriptions' ) ) {
    function dns_remove_user_from_subscriptions( $user_id ) {

        // Remove user from posts
        $posts = get_posts([
            'post_type'      => 'at_biz_dir',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'subscribed_users',
                    'value'   => $user_id,
                    'compare' => 'LIKE',
                ],
            ],
        ]);

        foreach ( $posts as $post ) {
            $users = get_post_meta( $post->ID, 'subscribed_users', true );
            if ( is_array( $users ) ) {
                $users = array_diff( $users, [ $user_id ] );
                update_post_meta( $post->ID, 'subscribed_users', $users );
            }
        }

        // Remove user from term meta
        $taxonomies = [ 'atbdp_listing_types', 'at_biz_dir-location' ];
        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms([ 'taxonomy' => $taxonomy, 'hide_empty' => false ]);

            foreach ( $terms as $term ) {
                $users = get_term_meta( $term->term_id, 'subscribed_users', true );
                if ( is_array( $users ) ) {
                    $users = array_diff( $users, [ $user_id ] );
                    update_term_meta( $term->term_id, 'subscribed_users', $users );
                }
            }
        }

        // Delete user meta
        delete_user_meta( $user_id, 'dns_notify_prefs' );
    }
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






?>
