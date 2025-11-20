<?php
/**
 * Subscribed Users Tab Template
 *
 * Variables passed:
 *   $subscribed_users - array of WP_User objects
 */
?>

<h2><?php esc_html_e( 'View all subscriptions', 'directorist-notification-system' ); ?></h2>

<?php if ( empty( $subscribed_users ) ) : ?>
    <p><?php esc_html_e( 'No subscribed users found.', 'directorist-notification-system' ); ?></p>
<?php else : ?>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'User', 'directorist-notification-system' ); ?></th>
                <th><?php esc_html_e( 'Market Place Listings', 'directorist-notification-system' ); ?></th>
                <th><?php esc_html_e( 'Job Listings', 'directorist-notification-system' ); ?></th>
                <th><?php esc_html_e( 'Locations', 'directorist-notification-system' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $subscribed_users as $user ) :

            // Get user preferences
            $prefs = get_user_meta( $user->ID, 'dns_notify_prefs', true );

            // Ensure all keys are present
            $prefs = wp_parse_args(
                is_array( $prefs ) ? $prefs : [],
                [
                    'market_types'      => [], // from Market tab (market_types[])
                    'listing_types'     => [], // from Job tab (listing_types[])
                    'listing_locations' => [], // from Location tab (listing_locations[])
                ]
            );

            ?>
            <tr>
                <!-- USER INFO -->
                <td>
                    <strong><?php echo esc_html( $user->display_name ); ?></strong><br>
                    <small><?php echo esc_html( $user->user_email ); ?></small>
                </td>

                <!-- MARKET PLACE LISTINGS -->
                <td>
                    <?php
                    if ( ! empty( $prefs['market_types'] ) && is_array( $prefs['market_types'] ) ) {
                        foreach ( $prefs['market_types'] as $tid ) {
                            $term = get_term( (int) $tid );
                            if ( $term && ! is_wp_error( $term ) ) {
                                echo esc_html( $term->name ) . '<br>';
                            }
                        }
                    } else {
                        echo '<em>' . esc_html__( 'None', 'directorist-notification-system' ) . '</em>';
                    }
                    ?>
                </td>

                <!-- JOB LISTINGS -->
                <td>
                    <?php
                    if ( ! empty( $prefs['listing_types'] ) && is_array( $prefs['listing_types'] ) ) {
                        foreach ( $prefs['listing_types'] as $tid ) {
                            $term = get_term( (int) $tid );
                            if ( $term && ! is_wp_error( $term ) ) {
                                echo esc_html( $term->name ) . '<br>';
                            }
                        }
                    } else {
                        echo '<em>' . esc_html__( 'None', 'directorist-notification-system' ) . '</em>';
                    }
                    ?>
                </td>

                <!-- LOCATIONS -->
                <td>
                    <?php
                    if ( ! empty( $prefs['listing_locations'] ) && is_array( $prefs['listing_locations'] ) ) {
                        foreach ( $prefs['listing_locations'] as $lid ) {
                            $term = get_term( (int) $lid );
                            if ( $term && ! is_wp_error( $term ) ) {
                                echo esc_html( $term->name ) . '<br>';
                            }
                        }
                    } else {
                        echo '<em>' . esc_html__( 'None', 'directorist-notification-system' ) . '</em>';
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
