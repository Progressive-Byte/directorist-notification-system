<?php
/**
 * Subscribed Users Tab Template
 *
 * @var WP_User[] $subscribed_users
 */

/**
 * Print term list with serial numbers
 *
 * @param array  $term_ids
 * @param string $text_domain
 */
function dns_print_terms_with_sl( $term_ids, $text_domain = 'directorist-notification-system' ) {
    if ( empty( $term_ids ) || ! is_array( $term_ids ) ) {
        echo '<em>' . esc_html__( 'None', $text_domain ) . '</em>';
        return;
    }

    $sl = 1;

    foreach ( $term_ids as $term_id ) {
        $term = get_term( (int) $term_id );

        if ( $term && ! is_wp_error( $term ) ) {
            echo esc_html( sprintf( '%d. %s', $sl, $term->name ) ) . '<br>';
            $sl++;
        }
    }
}
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
            <th><?php esc_html_e( 'Market Place Locations', 'directorist-notification-system' ); ?></th>
            <th><?php esc_html_e( 'Job Listings', 'directorist-notification-system' ); ?></th>
            <th><?php esc_html_e( 'Job Locations', 'directorist-notification-system' ); ?></th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ( $subscribed_users as $user ) : 

        // Get user prefs
        $prefs = get_user_meta( $user->ID, 'dns_notify_prefs', true );
        $prefs = is_array( $prefs ) ? $prefs : [];

        // Extract listings and locations
        $market_listings  = $prefs['market_types']['listing']   ?? [];
        $market_locations = $prefs['market_types']['locations'] ?? [];

        $job_listings     = $prefs['listing_types']['listing']  ?? [];
        $job_locations    = $prefs['listing_types']['locations'] ?? [];
        ?>
        <tr>
            <!-- USER -->
            <td>
                <strong><?php echo esc_html( $user->display_name ); ?></strong><br>
                <small><?php echo esc_html( $user->user_email ); ?></small>
            </td>

            <!-- MARKETPLACE -->
            <td>
                <?php dns_print_terms_with_sl( $market_listings ); ?>
            </td>
            <td>
                <?php dns_print_terms_with_sl( $market_locations ); ?>
            </td>

            <!-- JOB -->
            <td>
                <?php dns_print_terms_with_sl( $job_listings ); ?>
            </td>
            <td>
                <?php dns_print_terms_with_sl( $job_locations ); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>
