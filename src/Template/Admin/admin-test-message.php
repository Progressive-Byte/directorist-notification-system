<?php
/**
 * Test Message Tab Template
 *
 * Variables passed:
 *   $users - array of WP_User objects
 */
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
