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
    // Product toggle
    var $productToggle = $('input[name="np_product_enabled"]');
    var $productSelect = $('select[name="np_products[]"]');

    $productToggle.on('change', function() {
        $productSelect.prop('disabled', !$productToggle.is(':checked'));
    });

    // Job toggle
    var $jobToggle = $('input[name="np_job_enabled"]');
    var $jobChiplist = $('.dns-chiplist');

    $jobToggle.on('change', function() {
        if ($jobToggle.is(':checked')) {
            $jobChiplist.css({ 'pointer-events': 'auto', 'opacity': '1' });
        } else {
            $jobChiplist.css({ 'pointer-events': 'none', 'opacity': '0.6' });
        }
    });

    // City toggle
    var $cityToggle = $('input[name="np_city_enabled"]');
    var $citySelect = $('select[name="np_cities[]"]');

    $cityToggle.on('change', function() {
        $citySelect.prop('disabled', !$cityToggle.is(':checked'));
    });

    $('.dns-tab-btn').on('click', function(){
        var tab = $(this).data('tab');

        // Set active button
        $('.dns-tab-btn').removeClass('active');
        $(this).addClass('active');

        // Show corresponding content
        $('.dns-tab-content').removeClass('active').hide();
        $('#tab-' + tab).addClass('active').show();
    });

    // Optionally, show first tab by default
    $('.dns-tab-btn.active').trigger('click');

   
    $('#np_city_enabled').on('change', function(){
        $('#np_cities_select').prop('disabled', !$(this).is(':checked'));
    });
});
