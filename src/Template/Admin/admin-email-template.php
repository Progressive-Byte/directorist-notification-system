// Save form
if ( isset($_POST['dns_save_email_template']) && check_admin_referer('dns_save_email_template_nonce') ) {

    $subject = sanitize_text_field( wp_unslash($_POST['dns_email_default_subject']) );
    $body    = wp_kses_post( wp_unslash($_POST['dns_email_default_body']) );

    update_option('dns_email_default_subject', $subject);
    update_option('dns_email_default_body', $body);

    echo '<div class="updated notice"><p>' . esc_html__( 'Email template saved successfully.', 'dns' ) . '</p></div>';
}

$subject = get_option(
    'dns_email_default_subject',
    __( 'New Listing Match Found: {listing_title}', 'dns' )
);

$body = get_option(
    'dns_email_default_body',
    __(
        '<p>Hello {user_name},</p>
        <p>A new listing "{listing_title}" matches your preferences.</p>
        <p>Type: {listing_types}</p>
        <p>City: {listing_cities}</p>
        <p><a href="{listing_link}">View Listing</a></p>
        <p><a href="{unsubscribe_url}">Unsubscribe</a></p>',
        'dns'
    )
);
?>

<div class="dns-card">
    <h2><?php _e('Email Template Settings', 'dns'); ?></h2>

    <form method="post">
        <?php wp_nonce_field('dns_save_email_template_nonce'); ?>

        <table class="form-table">

            <tr>
                <th><label><?php _e('Email Subject', 'dns'); ?></label></th>
                <td>
                    <input type="text"
                           name="dns_email_default_subject"
                           value="<?php echo esc_attr($subject); ?>"
                           class="regular-text"
                           style="width: 100%;">
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Email Message', 'dns'); ?></label></th>
                <td>
                    <?php
                    wp_editor(
                        $body,
                        'dns_email_default_body',
                        [
                            'textarea_name' => 'dns_email_default_body',
                            'media_buttons' => false,
                            'textarea_rows' => 20,
                        ]
                    );
                    ?>
                </td>
            </tr>

        </table>

        <h3 class="dns-heading"><?php _e('Available Placeholders', 'dns'); ?></h3>

        <ul id="dns-placeholder-list" class="dns-placeholder-box">
            <?php
            $placeholders = [
                '{user_name}',
                '{listing_title}',
                '{listing_link}',
                '{listing_types}',
                '{listing_cities}',
                '{unsubscribe_url}',
            ];

            foreach ( $placeholders as $ph ) {
                echo '<li>
                        <span class="placeholder-tag">' . esc_html( $ph ) . '</span>
                        <button class="copy-btn" data-copy="' . esc_attr( $ph ) . '">' . esc_html__( 'Copy', 'dns' ) . '</button>
                    </li>';
            }
            ?>
        </ul>

        <p>
            <button type="submit" name="dns_save_email_template" class="button button-primary">
                <?php _e('Save Template', 'dns'); ?>
            </button>
        </p>

    </form>
</div>
