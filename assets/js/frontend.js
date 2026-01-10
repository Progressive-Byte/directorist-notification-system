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
jQuery(function($) {

    // Preserve original order
    const $locationList = $('.dns-location-list');
    const locationOriginal = $locationList.children('.dns-checkbox').toArray();

    const $listingList = $('.dns-listing-list');
    const listingOriginal = $listingList.children('.dns-checkbox').toArray();

    // Location search
    $(document).on('keyup', '#dns-location-search', function () {
        const q = $(this).val().toLowerCase();
        const $items = $locationList.children('.dns-checkbox');

        if (q === '') {
            // Restore original order if input is empty
            $locationList.html(locationOriginal);
            $locationList.children('.dns-checkbox').show();
        } else {
            // Sort matched first
            $items.sort(function (a, b) {
                const textA = $(a).text().toLowerCase();
                const textB = $(b).text().toLowerCase();
                const matchA = textA.indexOf(q) !== -1 ? 1 : 0;
                const matchB = textB.indexOf(q) !== -1 ? 1 : 0;
                return matchB - matchA;
            });

            $locationList.html($items);

            // Toggle visibility
            $items.each(function () {
                const txt = $(this).text().toLowerCase();
                $(this).toggle(txt.indexOf(q) !== -1);
            });
        }
    });

    // Listings search
    $(document).on('keyup', '#dns-listing-search', function () {
        const q = $(this).val().toLowerCase();
        const $items = $listingList.children('.dns-checkbox');

        if (q === '') {
            $listingList.html(listingOriginal);
            $listingList.children('.dns-checkbox').show();
        } else {
            $items.sort(function (a, b) {
                const textA = $(a).text().toLowerCase();
                const textB = $(b).text().toLowerCase();
                const matchA = textA.indexOf(q) !== -1 ? 1 : 0;
                const matchB = textB.indexOf(q) !== -1 ? 1 : 0;
                return matchB - matchA;
            });

            $listingList.html($items);

            $items.each(function () {
                const txt = $(this).text().toLowerCase();
                $(this).toggle(txt.indexOf(q) !== -1);
            });
        }
    });

});

jQuery(document).ready(function($) {

    const TAB_KEY = 'dns_active_tab';

    /**
     * ---------------------------
     * TAB HANDLING
     * ---------------------------
     */
    function activateTab(name) {
        if (!name) return;

        const $tabBtn = $('.dns-tab[data-tab="' + name + '"]');
        const $tabPanel = $('#tab-' + name);

        if ($tabBtn.length === 0 || $tabPanel.length === 0) return;

        $('.dns-tab').removeClass('active');
        $('.dns-tab-content').removeClass('active');

        $tabBtn.addClass('active');
        $tabPanel.addClass('active');

        try {
            localStorage.setItem(TAB_KEY, name);
        } catch (e) {}
    }

    // Initialize saved tab or first tab
    (function initActiveTab() {
        let saved = null;
        try { saved = localStorage.getItem(TAB_KEY); } catch (e) {}

        if (saved && $('.dns-tab[data-tab="' + saved + '"]').length && $('#tab-' + saved).length) {
            activateTab(saved);
            return;
        }

        const $firstBtn = $('.dns-tab').first();
        if ($firstBtn.length) activateTab($firstBtn.data('tab'));
    })();

    // Tab click
    $(document).on('click', '.dns-tab', function(e) {
        if ($(this).attr('type') === 'submit') e.preventDefault();
        activateTab($(this).data('tab'));
    });

    $(document).on('keydown', '.dns-tab', function(e) {
        const code = e.which || e.keyCode;
        if (code === 13 || code === 32) {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    /**
     * ---------------------------
     * ALERT AUTO HIDE
     * ---------------------------
     */
    const $alert = $('.dns-alert');
    if ($alert.length) {
        setTimeout(function() {
            $alert.fadeOut(400, function() { $(this).remove(); });
        }, 5000);
    }

    /**
     * ---------------------------
     * SEARCH FILTER
     * ---------------------------
     */
    jQuery(document).ready(function($) {
        $('.dns-search-wrapper').each(function() {
            var $wrapper = $(this);
            var $input = $wrapper.find('.dns-search-input');
            var $clearBtn = $wrapper.find('.dns-search-clear');

            // --------------------------
            // Show/hide cross icon & filter checkboxes on input
            // --------------------------
            $input.on('input', function() {
                var query = $input.val().toLowerCase();

                // Show/hide clear icon
                if (query.length > 0) {
                    $clearBtn.show();
                } else {
                    $clearBtn.hide();
                }

                // Filter checkboxes in this tab
                var $checkboxes = $wrapper.closest('.dns-tab-content').find('.dns-checkbox-list .dns-checkbox');
                $checkboxes.each(function() {
                    var labelText = $(this).text().toLowerCase();
                    $(this).toggle(labelText.indexOf(query) > -1);
                });
            });

            // --------------------------
            // Clear input when clicking cross
            // --------------------------
            $clearBtn.on('click', function() {
                $input.val('');
                $clearBtn.hide();

                // Reset all checkboxes visibility
                var $checkboxes = $wrapper.closest('.dns-tab-content').find('.dns-checkbox-list .dns-checkbox');
                $checkboxes.show();

                $input.focus();
            });
        });
    });

    /**
     * ---------------------------
     * CHECKBOX HANDLING
     * ---------------------------
     */
    $(document).on('click', '.dns-checkbox input[type="checkbox"]', function() {
        $(this).closest('.dns-checkbox').toggleClass('dns-checked', $(this).is(':checked'));
    });

    $(document).on('click', '.dns-select-all', function() {
        const $checkboxes = $(this).closest('.dns-tab-content').find('.dns-checkbox input[type="checkbox"]');
        $checkboxes.prop('checked', true).closest('.dns-checkbox').addClass('dns-checked');
    });

    $(document).on('click', '.dns-deselect-all', function() {
        const $checkboxes = $(this).closest('.dns-tab-content').find('.dns-checkbox input[type="checkbox"]');
        $checkboxes.prop('checked', false).closest('.dns-checkbox').removeClass('dns-checked');
    });

    /**
     * ---------------------------
     * SHOW SELECTED / SHOW ALL TOGGLE
     * ---------------------------
     */
    $(document).on('click', '.dns-show-selected', function() {
        const $btn = $(this).find('.dns-show-selected-text');
        const $checkboxes = $(this).closest('.dns-tab-content').find('.dns-checkbox-list .dns-checkbox');

        if ($btn.text().includes('Show Selected')) {
            $checkboxes.each(function() {
                if (!$(this).find('input[type="checkbox"]').is(':checked')) $(this).hide();
            });
            $btn.text('Show All');
        } else {
            $checkboxes.show();
            $btn.text('Show Selected');
        }
    });

    // Expose tab function globally if needed
    window.dnsActivateTab = activateTab;
});

jQuery(document).ready(function($){

    const $form = $('.dns-wrap form');
    const $submitBtn = $form.find('.dns-btn.dns-btn--primary');

    // Footer message container
    let $footerMsg = $('#dns-footer-message');
    if (!$footerMsg.length) {
        $footerMsg = $(`
            <div id="dns-footer-message" style="display:none; position:fixed; bottom:20px; left:50%; transform:translateX(-50%); 
                 padding:15px 20px; background:#ffe6e6; border:1px solid red; color:red; border-radius:6px; 
                 z-index:9999; font-weight:500;">
                <span id="dns-footer-close" style="cursor:pointer; float:right; margin-left:10px; font-weight:bold;">×</span>
                <span id="dns-footer-text"></span>
            </div>
        `);
        $('body').append($footerMsg);
    }

    const $footerText = $footerMsg.find('#dns-footer-text');
    const $footerClose = $footerMsg.find('#dns-footer-close');

    // Function to set cookie
    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days*24*60*60*1000));
        const expires = "expires="+ d.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }

    // Function to get cookie
    function getCookie(name) {
        const cname = name + "=";
        const decodedCookie = decodeURIComponent(document.cookie);
        const ca = decodedCookie.split(';');
        for(let i=0; i<ca.length; i++) {
            let c = ca[i].trim();
            if(c.indexOf(cname) == 0) return c.substring(cname.length, c.length);
        }
        return "";
    }

    // Hover event
    $submitBtn.on('mouseenter', function(){
        if(getCookie('dns_hover_msg_shown')) return; // already shown in last 5 days

        const marketChecked = $form.find('input[name="market_types[]"]:checked').length;
        const locationChecked = $form.find('input[name="listing_locations[]"]:checked').length;

        if(!marketChecked || !locationChecked){
            $footerText.text('Make sure you select both Listing/Marketplace and Location.');
            $footerMsg.fadeIn();

            // Auto hide after 5 seconds
            setTimeout(function(){
                $footerMsg.fadeOut();
            }, 5000);

            // Set cookie for 5 days
            setCookie('dns_hover_msg_shown', '1', 5);
        }
    });

    // Close on cross click
    $footerClose.on('click', function(){
        $footerMsg.fadeOut();
    });

});

jQuery(function($){

    // Toggle children on parent click
    $('.dns-location-parent').on('click', function() {
        var parentID = $(this).data('parent-id');
        var children = $('#dns-children-' + parentID);

        children.slideToggle();
    });

    // Select All
    $('.dns-select-all').on('click', function() {
        $(this).closest('.dns-tab-content').find('input[type=checkbox]').prop('checked', true);
    });

    // Deselect All
    $('.dns-deselect-all').on('click', function() {
        $(this).closest('.dns-tab-content').find('input[type=checkbox]').prop('checked', false);
    });

    // Show Selected
    $('.dns-show-selected').on('click', function() {
        $(this).closest('.dns-tab-content').find('.dns-checkbox').hide();
        $(this).closest('.dns-tab-content').find('input:checked').closest('.dns-checkbox').show();
    });

});


