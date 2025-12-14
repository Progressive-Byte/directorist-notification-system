jQuery(document).ready(function($) {

    // === Toggle Job/Product page select ===
    $('#dns_subscribe_job').on('change', function() {
        $('#dns_job_page_select').toggle(this.checked);
    });

    $('#dns_subscribe_product').on('change', function() {
        $('#dns_product_page_select').toggle(this.checked);
    });

    // === Show/hide pages list when toggle is clicked ===
    $('#dns_subscribe_pages').on('change', function() {
        if ($(this).is(':checked')) {
            $('#dns_pages_select').slideDown();
        } else {
            $('#dns_pages_select').slideUp();
        }
    });

    // === Tabs handling (conflict-free) ===
    function dnsAdminTabs() {
        var wrapper = $('.dns-tab-wrapper');
        var tabs    = wrapper.find('.dns-tab');
        var contents = $('.dns-tab-content');

        // Show last active tab
        var lastTab = localStorage.getItem('dns_active_tab');
        if (lastTab && $(lastTab).length) {
            tabs.removeClass('dns-tab-active');
            contents.hide();
            tabs.filter('[href="' + lastTab + '"]').addClass('dns-tab-active');
            $(lastTab).show();
        } else {
            tabs.removeClass('dns-tab-active');
            tabs.first().addClass('dns-tab-active');
            contents.hide();
            contents.first().show();
        }

        // Tabs switching
        tabs.off('click.dnsTabs').on('click.dnsTabs', function(e) {
            e.preventDefault();
            var tab_id = $(this).attr('href');

            // Activate tab
            tabs.removeClass('dns-tab-active');
            $(this).addClass('dns-tab-active');

            // Show corresponding content
            contents.hide();
            $(tab_id).show();

            // Save active tab
            localStorage.setItem('dns_active_tab', tab_id);
        });
    }

    dnsAdminTabs();
});

jQuery(document).ready(function($) {

    $('.copy-btn').on('click', function() {
        var text = $(this).data('copy');
        var btn  = $(this);

        // Create temporary input field
        var temp = $("<input>");
        $("body").append(temp);
        temp.val(text).select();
        document.execCommand("copy");
        temp.remove();

        // Change button state
        btn.text("Copied!").addClass("copied");

        setTimeout(function() {
            btn.text("Copy").removeClass("copied");
        }, 1000);
    });

});


jQuery(document).ready(function($) {
    // Get current post type from hidden input
    var postType = $('#post_type').val();

    if (postType === 'at_biz_dir') {
        $('#publish').hover(function() {
            $('input[name^="custom_address"]').each(function() {
                if ($(this).is(':disabled')) {
                    $(this).prop('disabled', false);
                }
            });
        });
    }
});


(function($){
    'use strict';

    function initAddressFields() {
        if ($('.address-add-btn').data('initialized')) return;
        $('.address-add-btn').data('initialized', true);

        var addressFieldValues = {};

        // Initialize fields and snapshot values
        function protectFields() {
            $('.address-field-wrapper input[data-address-field="true"]').each(function(index){
                var fieldId = 'address-field-' + index;
                $(this).attr('data-field-id', fieldId);

                // Set first field editable, others look disabled
                if(index === 0){
                    $(this).prop('disabled', false).removeClass('disabled-field');
                } else {
                    $(this).prop('disabled', true).addClass('disabled-field');
                }

                // Store initial value
                if(addressFieldValues[fieldId] === undefined){
                    addressFieldValues[fieldId] = $(this).val();
                }

                // Set proper name
                $(this).attr('name', 'custom_address[' + index + ']');
            });
        }

        // Prevent other scripts from injecting data
        function monitorChanges() {
            $('.address-field-wrapper input[data-address-field="true"]').each(function(){
                var $this = $(this);
                var fieldId = $this.attr('data-field-id');

                if($this.val() !== addressFieldValues[fieldId] && $this.is('.disabled-field')){
                    $this.val(addressFieldValues[fieldId]);
                }
            });
        }

        setInterval(monitorChanges, 50);

        // Activate a field
        function setActiveField($input){
            $('.address-field-wrapper input[data-address-field="true"]').removeClass('active-field').addClass('disabled-field');
            $input.removeClass('disabled-field').addClass('active-field').prop('disabled', false).focus();

            // Update snapshot
            var fieldId = $input.attr('data-field-id');
            addressFieldValues[fieldId] = $input.val();
        }

        // Reindex field names
        function reindexFields(){
            $('.address-field-wrapper input[data-address-field="true"]').each(function(i){
                $(this).attr('name', 'custom_address[' + i + ']');
                var fieldId = $(this).attr('data-field-id');
                addressFieldValues[fieldId] = $(this).val();
            });
        }

        // Add new field
        $(document).on('click', '.address-add-btn', function(e){
            e.preventDefault();
            var wrapper = $('.address-fields-container');
            var lastFieldWrapper = wrapper.find('.address-field-wrapper:last');
            var lastInput = lastFieldWrapper.find('input[data-address-field="true"]');

            // Check if previous input is empty
            if($.trim(lastInput.val()) === '') {
                toastr.error('Please fill out the previous address field before adding a new one!', 'Error');
                lastInput.focus();
                return; // Stop adding a new field
            }

            // Disable previous input
            lastInput.prop('disabled', true).addClass('disabled-field');

            // Clone first field for new input
            var newField = wrapper.find('.address-field-wrapper:first').clone();
            var newInput = newField.find('input');

            newInput.val('')
                    .removeClass('active-field disabled-field')
                    .attr('data-address-field', 'true')
                    .prop('disabled', false)
                    .focus();

            newField.find('.address_result ul').empty();
            newField.find('.address-remove-btn').show();

            wrapper.append(newField);

            protectFields();
            reindexFields();
            setActiveField(newInput);
        });

        // Remove field
        $(document).on('click', '.address-remove-btn', function(e){
            e.preventDefault();
            if($('.address-field-wrapper').length > 1){
                var $removed = $(this).closest('.address-field-wrapper');
                var wasActive = $removed.find('input').hasClass('active-field');
                var fieldId = $removed.find('input').attr('data-field-id');
                delete addressFieldValues[fieldId];

                $removed.remove();
                reindexFields();

                if(wasActive){
                    setActiveField($('.address-field-wrapper:last input[data-address-field="true"]'));
                }
            }
        });

        // Click or focus to activate
        // $(document).on('focus click', '.address-field-wrapper input[data-address-field="true"]', function(){
        //     setActiveField($(this));
        // });

        // Only active field updates snapshot
        $(document).on('input change', '.address-field-wrapper input[data-address-field="true"].active-field', function(){
            var fieldId = $(this).attr('data-field-id');
            addressFieldValues[fieldId] = $(this).val();
        });

        // Initialize fields
        setTimeout(function(){
            protectFields();
            var $firstInput = $('.address-field-wrapper:first input[data-address-field="true"]');
            $firstInput.addClass('active-field').prop('disabled', false);
        }, 10);
    }

    $(document).ready(initAddressFields);
    $(window).on('load', initAddressFields);

})(jQuery);



