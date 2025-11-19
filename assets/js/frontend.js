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
    $('.dns-search-input').on('input', function() {
        const query = $(this).val().toLowerCase();
        const $checkboxes = $(this).closest('.dns-tab-content').find('.dns-checkbox-list .dns-checkbox');

        $checkboxes.each(function() {
            const labelText = $(this).text().toLowerCase();
            $(this).toggle(labelText.indexOf(query) > -1);
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
