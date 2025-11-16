 jQuery(document).ready(function($){
    // Toggle Job/Product page select
    $('#dns_subscribe_job').change(function(){
        $('#dns_job_page_select').toggle(this.checked);
    });
    $('#dns_subscribe_product').change(function(){
        $('#dns_product_page_select').toggle(this.checked);
    });

    // Tabs switching
    $('.nav-tab-wrapper .nav-tab').click(function(e){
        e.preventDefault();
        var tab_id = $(this).attr('href');

        // Toggle active class
        $(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active');

        // Show selected tab content
        $('.tab-content').hide();
        $(tab_id).show();
    });
});