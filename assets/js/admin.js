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

