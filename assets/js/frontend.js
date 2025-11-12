jQuery(document).ready(function($){
    console.log("Directory Notification System Admin JS loaded.");

    // Example: delete notification button
    $('.dns-delete-notification').on('click', function(e){
        e.preventDefault();
        if(confirm("Are you sure you want to delete this notification?")){
            $(this).closest('tr').fadeOut();
        }
    });
});

jQuery(document).ready(function($) {
    // Initialize Select2 for all select fields
    // $('.dns-select').select2({
    //     width: '300px', 
    //     placeholder: 'Select options'
    // });

    // Enable/disable select based on toggle checkbox
    $('#np_job_enabled').on('change', function() {
        $('#np_jobs').prop('disabled', !this.checked).trigger('change');
    });
    $('#np_product_enabled').on('change', function() {
        $('#np_products').prop('disabled', !this.checked).trigger('change');
    });
    $('#np_city_enabled').on('change', function() {
        $('#np_cities').prop('disabled', !this.checked).trigger('change');
    });

    // Tab system
    $('.dns-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        $('.dns-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.dns-tab-content').removeClass('active').hide();
        $('#tab-' + tab).addClass('active').show();
    });
});