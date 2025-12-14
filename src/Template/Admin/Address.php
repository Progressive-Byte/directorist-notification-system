<?php
/**
 * @author  wpWax
 * @since   6.6
 * @version 7.0.5.6
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $listing_form ) && is_object( $listing_form ) ) {
    $post_id = intval( $listing_form->add_listing_id ); 

}

$addresses = get_post_meta( $post_id, 'custom_address', true );

// var_dump( $addresses );
?>
<div class="directorist-form-group directorist-form-address-field" id="address-fields-wrapper">
    <?php $listing_form->field_label_template( $data );?>

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
                <button type="button" class="address-remove-btn" style="<?php echo $index === 0 ? 'display:none;' : ''; ?>">−</button>
                <div class="address_result"><ul></ul></div>
            </div>
        <?php endforeach; ?>

    </div>

    <button type="button" class="address-add-btn">+ Add Another Address</button>
</div>