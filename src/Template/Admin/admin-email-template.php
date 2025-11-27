<?php
// Save form
if ( isset($_POST['dns_save_email_template']) && check_admin_referer('dns_save_email_template_nonce') ) {

    $subject = sanitize_text_field( wp_unslash($_POST['dns_email_default_subject']) );
    $body    = wp_kses_post( wp_unslash($_POST['dns_email_default_body']) );

    update_option('dns_email_default_subject', $subject);
    update_option('dns_email_default_body', $body);

    echo '<div class="updated notice"><p>Email template saved successfully.</p></div>';
}

$subject = get_option('dns_email_default_subject', 'New Listing Match Found: {listing_title}');
$body    = get_option('dns_email_default_body', '
    <p>Hello {user_name},</p>
    <p>A new listing "{listing_title}" matches your preferences.</p>
    <p>Type: {listing_types}</p>
    <p>City: {listing_cities}</p>
    <p><a href="{listing_link}">View Listing</a></p>
    <p><a href="{unsubscribe_url}">Unsubscribe</a></p>
');
?>

<div class="dns-card">
    <h2>Email Template Settings</h2>

    <form method="post">
        <?php wp_nonce_field('dns_save_email_template_nonce'); ?>

        <table class="form-table">

            <tr>
                <th><label>Email Subject</label></th>
                <td>
                    <input type="text" name="dns_email_default_subject"
                           value="<?php echo esc_attr($subject); ?>"
                           class="regular-text" style="width: 100%;">
                </td>
            </tr>

            <tr>
                <th><label>Email Message</label></th>
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

        <h3 class="dns-heading">Available Placeholders</h3>

        <ul id="dns-placeholder-list" class="dns-placeholder-box">
            <li>
                <span class="placeholder-tag">{user_name}</span>
                <button class="copy-btn" data-copy="{user_name}">Copy</button>
            </li>
            <li>
                <span class="placeholder-tag">{listing_title}</span>
                <button class="copy-btn" data-copy="{listing_title}">Copy</button>
            </li>
            <li>
                <span class="placeholder-tag">{listing_link}</span>
                <button class="copy-btn" data-copy="{listing_link}">Copy</button>
            </li>
            <li>
                <span class="placeholder-tag">{listing_types}</span>
                <button class="copy-btn" data-copy="{listing_types}">Copy</button>
            </li>
            <li>
                <span class="placeholder-tag">{listing_cities}</span>
                <button class="copy-btn" data-copy="{listing_cities}">Copy</button>
            </li>
            <li>
                <span class="placeholder-tag">{unsubscribe_url}</span>
                <button class="copy-btn" data-copy="{unsubscribe_url}">Copy</button>
            </li>
        </ul>



        <p><button type="submit" name="dns_save_email_template" class="button button-primary">Save Template</button></p>
    </form>
</div>
