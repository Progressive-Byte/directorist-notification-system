<?php
/**
 * Subscribed Users Tab Template
 *
 * Variables passed:
 *   $subscribed_users - array of WP_User objects
 */
?>

<h2><?php esc_html_e('View all subscriptions', 'directorist-notification-system'); ?></h2>

<?php if ( empty( $subscribed_users ) ) : ?>
    <p><?php esc_html_e( 'No subscribed users found.', 'directorist-notification-system' ); ?></p>
<?php else : ?>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('User', 'directorist-notification-system'); ?></th>
                <th><?php esc_html_e('Listing Types', 'directorist-notification-system'); ?></th>
                <th><?php esc_html_e('Locations', 'directorist-notification-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $subscribed_users as $user ) :
                $prefs = get_user_meta( $user->ID, 'dns_notify_prefs', true );
                $prefs = wp_parse_args( $prefs, [
                    'listing_types'     => [],
                    'listing_locations' => [],
                ] );
            ?>
            <tr>
                <td>
                    <strong><?php echo esc_html( $user->display_name ); ?></strong><br>
                    <small><?php echo esc_html( $user->user_email ); ?></small>
                </td>
                <td>
                    <?php
                    if ( ! empty( $prefs['listing_types'] ) ) {
                        foreach ( $prefs['listing_types'] as $tid ) {
                            $term = get_term( $tid );
                            if ( $term ) {
                                echo esc_html( $term->name ) . '<br>';
                            }
                        }
                    } else {
                        echo '<em>' . esc_html__( 'None', 'directorist-notification-system' ) . '</em>';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if ( ! empty( $prefs['listing_locations'] ) ) {
                        foreach ( $prefs['listing_locations'] as $lid ) {
                            $term = get_term( $lid );
                            if ( $term ) {
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
