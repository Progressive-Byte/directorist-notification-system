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

    // --- Tab Switch ---
    $('.dns-tab').on('click', function() {
        const target = $(this).data('tab');

        // Remove active from all
        $('.dns-tab').removeClass('active');
        $('.dns-tab-content').removeClass('active');

        // Activate selected
        $(this).addClass('active');
        $('#tab-' + target).addClass('active');
    });

    // --- Auto-hide success message ---
    const $alert = $('.dns-alert');
    if ($alert.length) {
        setTimeout(function() {
            $alert.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }

});
