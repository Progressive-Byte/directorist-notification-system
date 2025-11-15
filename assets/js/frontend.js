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

    // Utility: activate a tab by name (e.g. "types", "locations", "listings")
    function activateTab(name) {
        if (!name) return;

        const $tabBtn = $('.dns-tab[data-tab="' + name + '"]');
        const $tabPanel = $('#tab-' + name);

        // If either doesn't exist, abort
        if ($tabBtn.length === 0 || $tabPanel.length === 0) return;

        // remove active from all
        $('.dns-tab').removeClass('active');
        $('.dns-tab-content').removeClass('active');

        // set active
        $tabBtn.addClass('active');
        $tabPanel.addClass('active');

        // Remember selection
        try {
            localStorage.setItem(TAB_KEY, name);
        } catch (e) {
            // ignore storage errors (e.g. Safari private mode)
        }
    }

    // Init: restore saved tab, or keep existing 'active' button/panel if present
    (function initActiveTab() {
        let saved = null;
        try {
            saved = localStorage.getItem(TAB_KEY);
        } catch (e) {
            saved = null;
        }

        if (saved) {
            // If saved tab exists in DOM, activate it
            if ($('.dns-tab[data-tab="' + saved + '"]').length && $('#tab-' + saved).length) {
                activateTab(saved);
                return;
            }
        }

        // Otherwise ensure exactly one active tab/panel exists:
        const $activeBtn = $('.dns-tab.active').first();
        if ($activeBtn.length) {
            const t = $activeBtn.data('tab');
            if ($('#tab-' + t).length) {
                activateTab(t);
                return;
            }
        }

        // Fallback: activate first tab/button that matches a panel
        const $firstBtn = $('.dns-tab').filter(function() {
            return $('#tab-' + $(this).data('tab')).length;
        }).first();

        if ($firstBtn.length) {
            activateTab($firstBtn.data('tab'));
        }
    })();

    // Click handler for tab buttons (delegated in case buttons are injected later)
    $(document).on('click', '.dns-tab', function(e) {
        // If the button is inside a form and type="submit", prevent accidental submit
        if ($(this).attr('type') === 'submit') {
            e.preventDefault();
        }

        const tabName = $(this).data('tab');
        activateTab(tabName);
    });

    // Keyboard accessibility: allow Enter/Space to toggle when focused
    $(document).on('keydown', '.dns-tab', function(e) {
        const code = e.which || e.keyCode;
        if (code === 13 || code === 32) { // Enter or Space
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    // Auto-hide success message (if present)
    const $alert = $('.dns-alert');
    if ($alert.length) {
        setTimeout(function() {
            $alert.fadeOut(400, function() { $(this).remove(); });
        }, 5000);
    }

    // Optional: expose function to switch tab from elsewhere (callable)
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



