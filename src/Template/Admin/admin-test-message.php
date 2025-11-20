<?php
// Handle Test Message form submission
if ( isset( $_POST['test_message_submit'] ) ) {

    $user_id    = intval( $_POST['user_id'] ?? 0 );
    $raw_message = $_POST['message'] ?? '';
    $message    = sanitize_textarea_field( $raw_message );
    $send_email = ! empty( $_POST['send_email'] );
    $send_bp    = ! empty( $_POST['send_bp'] );

    if ( $user_id && $message ) {

        // Try to detect a post ID placeholder in the message, e.g. "New listing ##123##"
        $test_post_id   = 0;
        $test_post_link = '';
        $test_post_title = '';

        if ( preg_match( '/##(\d+)##/', $message, $matches ) ) {
            $test_post_id = intval( $matches[1] );
            if ( $test_post_id ) {
                $post_obj = get_post( $test_post_id );
                if ( $post_obj && $post_obj instanceof WP_Post ) {
                    $test_post_title = get_the_title( $test_post_id );
                    $test_post_link  = get_permalink( $test_post_id );
                }
            }
            // Remove the placeholder token from the visible message
            $message = str_replace( $matches[0], '', $message );
            $message = trim( $message );
        }

        // --- BuddyPress Notification ---
        if ( $send_bp && function_exists( 'bp_notifications_add_notification' ) ) {
            bp_notifications_add_notification( [
                'user_id'           => $user_id,
                'item_id'           => 0,
                'secondary_item_id' => 0,
                'component_name'    => 'dns_matches',
                'component_action'  => 'test_message',
                'is_new'            => 1,
                'allow_duplicate'   => false,
            ] );

            // Optionally update meta
            $notifications = bp_notifications_get_notifications_for_user( $user_id, 'object' );
            if ( ! empty( $notifications ) ) {
                $last = reset( $notifications );
                bp_notifications_update_meta( $last->id, 'message', $message );
            }
        }

        // --- Email with design + optional listing block from ##post_id## ---
        if ( $send_email ) {
            $user = get_user_by( 'ID', $user_id );
            if ( $user && is_email( $user->user_email ) ) {

                $site_name     = get_bloginfo( 'name' );
                $subject       = sprintf(
                    /* translators: %s: site name */
                    __( 'DNS Test Notification – %s', 'directorist-notification-system' ),
                    $site_name
                );
                $message_html  = nl2br( esc_html( $message ) );

                ob_start();
                ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html( $subject ); ?></title>
</head>
<body bgcolor="#F3F4F6" style="padding:0;">

<!-- Outer wrapper -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F3F4F6">
    <tr>
        <td align="center">

            <!-- Spacer top -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr><td height="20">&nbsp;</td></tr>
            </table>

            <!-- Main card -->
            <table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF">
                <!-- Header row -->
                <tr>
                    <td bgcolor="#2563EB" style="padding:14px 16px; font-family:Arial,Helvetica,sans-serif; font-size:18px; color:#FFFFFF;">
                        <?php esc_html_e( 'Test Notification Email', 'directorist-notification-system' ); ?>
                    </td>
                </tr>

                <!-- Spacer -->
                <tr>
                    <td>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr><td height="16">&nbsp;</td></tr>
                        </table>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:0 16px 16px 16px; font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111111;">

                        <!-- Greeting -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="font-size:14px; color:#111111;">
                                    <?php
                                    printf(
                                        /* translators: %s: user display name */
                                        esc_html__( 'Hello %s,', 'directorist-notification-system' ),
                                        esc_html( $user->display_name )
                                    );
                                    ?>
                                </td>
                            </tr>
                            <tr><td height="10">&nbsp;</td></tr>
                            <tr>
                                <td style="font-size:14px; color:#444444;">
                                    <?php esc_html_e( 'This is a test notification email from Directorist Notification System.', 'directorist-notification-system' ); ?>
                                </td>
                            </tr>
                        </table>

                        <!-- Spacer -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr><td height="12">&nbsp;</td></tr>
                        </table>

                        <?php if ( $test_post_id && $test_post_title ) : ?>
                            <!-- Optional Listing Details (from ##post_id##) -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="10" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;">
                                <tr>
                                    <td style="font-size:14px; color:#111111;">
                                        <strong><?php esc_html_e( 'Listing Details (from post ID):', 'directorist-notification-system' ); ?></strong><br><br>
                                        <span style="font-size:13px; color:#444444;">
                                            <strong><?php esc_html_e( 'Title:', 'directorist-notification-system' ); ?></strong>
                                            <?php if ( $test_post_link ) : ?>
                                                <a href="<?php echo esc_url( $test_post_link ); ?>" style="color:#2563EB; text-decoration:none;">
                                                    <?php echo esc_html( $test_post_title ); ?>
                                                </a>
                                            <?php else : ?>
                                                <?php echo esc_html( $test_post_title ); ?>
                                            <?php endif; ?>
                                            <br />
                                            <strong><?php esc_html_e( 'Post ID:', 'directorist-notification-system' ); ?></strong>
                                            <?php echo esc_html( $test_post_id ); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Spacer -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr><td height="12">&nbsp;</td></tr>
                            </table>
                        <?php endif; ?>

                        <!-- Message content box -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="10" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;">
                            <tr>
                                <td style="font-size:14px; color:#111111;">
                                    <strong><?php esc_html_e( 'Message:', 'directorist-notification-system' ); ?></strong><br><br>
                                    <span style="font-size:13px; color:#444444;">
                                        <?php echo $message_html; // already escaped + nl2br ?>
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <!-- Spacer -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr><td height="18">&nbsp;</td></tr>
                        </table>

                        <!-- Footer note -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="font-size:11px; color:#999999;">
                                    <?php esc_html_e( 'You are seeing this because an administrator sent a test notification.', 'directorist-notification-system' ); ?>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <!-- Footer row -->
                <tr>
                    <td bgcolor="#F9FAFB" style="padding:10px 16px; font-family:Arial,Helvetica,sans-serif; font-size:11px; color:#999999;" align="center">
                        &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?>.
                        <?php esc_html_e( 'All rights reserved.', 'directorist-notification-system' ); ?>
                    </td>
                </tr>
            </table>

            <!-- Spacer bottom -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr><td height="20">&nbsp;</td></tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
                <?php
                $html_email = ob_get_clean();

                wp_mail(
                    $user->user_email,
                    $subject,
                    $html_email,
                    [ 'Content-Type: text/html; charset=UTF-8' ]
                );
            }
        }

        echo '<div class="notice notice-success"><p>' . esc_html__( 'Test message sent!', 'directorist-notification-system' ) . '</p></div>';

    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Please select a user and enter a message.', 'directorist-notification-system' ) . '</p></div>';
    }
}
?>

<h2><?php esc_html_e( 'Test Message', 'directorist-notification-system' ); ?></h2>

<form method="post">
    <table class="form-table">
        <tr>
            <th><label for="user_id"><?php esc_html_e( 'Select User', 'directorist-notification-system' ); ?></label></th>
            <td>
                <select name="user_id" id="user_id" required>
                    <option value="">-- <?php esc_html_e( 'Select User', 'directorist-notification-system' ); ?> --</option>
                    <?php foreach ( $users as $user ) : ?>
                        <option value="<?php echo esc_attr( $user->ID ); ?>">
                            <?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $user->user_email ); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e( 'To attach a listing to this test, include its ID in your message like: "Check listing ##123##  ".', 'directorist-notification-system' ); ?>
                </p>
            </td>
        </tr>

        <tr>
            <th><label for="message"><?php esc_html_e( 'Message', 'directorist-notification-system' ); ?></label></th>
            <td>
                <textarea name="message" id="message" rows="5" cols="50" required></textarea>
            </td>
        </tr>

        <tr>
            <th><?php esc_html_e( 'Send Method', 'directorist-notification-system' ); ?></th>
            <td>
                <label><input type="checkbox" name="send_email" value="1"> <?php esc_html_e( 'Email', 'directorist-notification-system' ); ?></label><br>
                <label><input type="checkbox" name="send_bp" value="1"> <?php esc_html_e( 'BuddyPress Notification', 'directorist-notification-system' ); ?></label>
            </td>
        </tr>
    </table>

    <p>
        <input type="submit" name="test_message_submit" class="button button-primary" value="<?php esc_attr_e( 'Send Test', 'directorist-notification-system' ); ?>">
    </p>
</form>
