<?php
/**
 * @author  wpWax
 * @since   6.6
 * @version 7.0.5.6
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = 0;

if ( isset( $listing_form ) && is_object( $listing_form ) ) {
    $post_id = intval( $listing_form->add_listing_id );
}

// Get saved addresses
$addresses = get_post_meta( $post_id, '_custom_address', true );

// Ensure at least one empty field
if ( empty( $addresses ) || ! is_array( $addresses ) ) {
    $addresses = [''];
}
?>

<div class="directorist-form-group directorist-form-address-field" id="address-fields-wrapper">

    <?php $listing_form->field_label_template( $data ); ?>

    <div class="address-fields-container">

        <?php foreach ( $addresses as $index => $address_value ) : ?>
            <div class="address-field-wrapper">
                <input
                    type="text"
                    autocomplete="off"
                    name="<?php echo esc_attr( $data['field_key'] ); ?>[]"
                    class="directorist-form-element directorist-location-js"
                    value="<?php echo esc_attr( $address_value ); ?>"
                    placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>"
                    <?php $listing_form->required( $data ); ?>
                    data-address-field="true"
                >

                <button
                    type="button"
                    class="address-remove-btn"
                    style="<?php echo $index === 0 ? 'display:none;' : ''; ?>"
                >
                    −
                </button>

                <div class="address_result"><ul></ul></div>
            </div>
        <?php endforeach; ?>

    </div>

    <?php if ( function_exists( 'dns_is_multiple_address_enabled' ) && dns_is_multiple_address_enabled() ) : ?>
        <button type="button" class="address-add-btn">
            + <?php esc_html_e( 'Add Another Address', 'directorist-notification-system' ); ?>
        </button>
    <?php endif; ?>

</div>
