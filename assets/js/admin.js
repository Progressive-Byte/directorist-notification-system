jQuery(document).ready(function($) {
    // Toggle Job/Product page select
    $('#dns_subscribe_job').change(function() {
        $('#dns_job_page_select').toggle(this.checked);
    });

    $('#dns_subscribe_product').change(function() {
        $('#dns_product_page_select').toggle(this.checked);
    });

    // Show last selected tab or default to Settings
    var lastTab = localStorage.getItem('dns_active_tab');
    if (lastTab && $(lastTab).length) {
        // Show stored tab
        $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
        $('.tab-content').hide();
        $('.nav-tab-wrapper .nav-tab[href="' + lastTab + '"]').addClass('nav-tab-active');
        $(lastTab).show();
    } else {
        // Default to first tab: Settings
        $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
        $('.nav-tab-wrapper .nav-tab').first().addClass('nav-tab-active');
        $('.tab-content').hide();
        $('.tab-content').first().show();
    }

    // Tabs switching
    $('.nav-tab-wrapper .nav-tab').click(function(e) {
        e.preventDefault();
        var tab_id = $(this).attr('href');

        // Toggle active class
        $(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active');

        // Show selected tab content
        $('.tab-content').hide();
        $(tab_id).show();

        // Store active tab in localStorage
        localStorage.setItem('dns_active_tab', tab_id);
    });
});



