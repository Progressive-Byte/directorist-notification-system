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

    const TAB_KEY = 'dns_active_tab';

    /**
     * ---------------------------------------------
     *  TAB HANDLING (your existing logic)
     * ---------------------------------------------
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

    (function initActiveTab() {
        let saved = null;

        try {
            saved = localStorage.getItem(TAB_KEY);
        } catch (e) {}

        if (saved &&
            $('.dns-tab[data-tab="' + saved + '"]').length &&
            $('#tab-' + saved).length
        ) {
            activateTab(saved);
            return;
        }

        const $activeBtn = $('.dns-tab.active').first();
        if ($activeBtn.length) {
            const t = $activeBtn.data('tab');
            if ($('#tab-' + t).length) {
                activateTab(t);
                return;
            }
        }

        const $firstBtn = $('.dns-tab').filter(function() {
            return $('#tab-' + $(this).data('tab')).length;
        }).first();

        if ($firstBtn.length) {
            activateTab($firstBtn.data('tab'));
        }
    })();

    $(document).on('click', '.dns-tab', function(e) {
        if ($(this).attr('type') === 'submit') {
            e.preventDefault();
        }
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
     * ---------------------------------------------
     *  AUTO HIDE ALERT MESSAGE
     * ---------------------------------------------
     */
    const $alert = $('.dns-alert');
    if ($alert.length) {
        setTimeout(function() {
            $alert.fadeOut(400, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Allow external tab switching
     */
    window.dnsActivateTab = activateTab;

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
    const $locationList = $('.dns-location-list');
    const $toggleBtn = $('#dns-show-selected-locations');
    let showingSelected = false; // Track toggle state

    $toggleBtn.on('click', function(e) {
        e.preventDefault();

        if (!showingSelected) {
            // Show only checked items
            $locationList.children('.dns-checkbox').hide();
            $locationList.children('.dns-checked').show();
            $toggleBtn.text('Show All'); // Button shows 'Show All'
        } else {
            // Show all items
            $locationList.children('.dns-checkbox').show();
            $toggleBtn.text('Show Selected'); // Button shows 'Show Selected'
        }

        // Toggle state
        showingSelected = !showingSelected;
    });

    // Dynamically add/remove dns-checked class when checkboxes are clicked
    $locationList.on('change', 'input[type="checkbox"]', function() {
        const $label = $(this).closest('.dns-checkbox');
        if ($(this).is(':checked')) {
            $label.addClass('dns-checked');
        } else {
            $label.removeClass('dns-checked');
        }
    });
});







