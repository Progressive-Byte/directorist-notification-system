<?php
// Handle Test Message form submission
if ( isset( $_POST['test_message_submit'] ) ) {

    $user_id    = intval( $_POST['user_id'] ?? 0 );
    $message    = sanitize_textarea_field( $_POST['message'] ?? '' );
    $send_email = ! empty( $_POST['send_email'] );
    $send_bp    = ! empty( $_POST['send_bp'] );

    if ( $user_id && $message ) {

        // --- BuddyPress Notification ---
        if ( $send_bp && function_exists('bp_notifications_add_notification') ) {
            bp_notifications_add_notification([
                'user_id'           => $user_id,
                'item_id'           => 0,
                'secondary_item_id' => 0,
                'component_name'    => 'dns_matches',
                'component_action'  => 'test_message',
                'is_new'            => 1,
                'allow_duplicate'   => false,
            ]);

            // Optionally update meta
            $notifications = bp_notifications_get_notifications_for_user( $user_id, 'object' );
            if ( ! empty( $notifications ) ) {
                $last = reset( $notifications );
                bp_notifications_update_meta( $last->id, 'message', $message );
            }
        }

        // --- Email ---
        if ( $send_email ) {
            $user = get_user_by( 'ID', $user_id );
            if ( $user && is_email( $user->user_email ) ) {
                wp_mail( $user->user_email, 'Test Notification', $message );
            }
        }

        echo '<div class="notice notice-success"><p>' . esc_html__( 'Test message sent!', 'dns' ) . '</p></div>';

    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Please select a user and enter a message.', 'dns' ) . '</p></div>';
    }
}
?>


<h2><?php esc_html_e('Test Message', 'dns'); ?></h2>

<form method="post">
    <table class="form-table">
        <tr>
            <th><label for="user_id"><?php esc_html_e('Select User', 'dns'); ?></label></th>
            <td>
                <select name="user_id" id="user_id" required>
                    <option value="">-- <?php esc_html_e('Select User', 'dns'); ?> --</option>
                    <?php foreach ( $users as $user ) : ?>
                        <option value="<?php echo esc_attr( $user->ID ); ?>">
                            <?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $user->user_email ); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <tr>
            <th><label for="message"><?php esc_html_e('Message', 'dns'); ?></label></th>
            <td>
                <textarea name="message" id="message" rows="5" cols="50" required></textarea>
            </td>
        </tr>

        <tr>
            <th><?php esc_html_e('Send Method', 'dns'); ?></th>
            <td>
                <label><input type="checkbox" name="send_email" value="1"> <?php esc_html_e('Email', 'dns'); ?></label><br>
                <label><input type="checkbox" name="send_bp" value="1"> <?php esc_html_e('BuddyPress Notification', 'dns'); ?></label>
            </td>
        </tr>
    </table>

    <p>
        <input type="submit" name="test_message_submit" class="button button-primary" value="<?php esc_attr_e('Send Test', 'dns'); ?>">
    </p>
</form>
