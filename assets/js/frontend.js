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
     * SEARCH FILTER WITH PARENT AUTO-EXPAND
     * ---------------------------
     */
    $('.dns-search-wrapper').each(function() {
        var $wrapper = $(this);
        var $input = $wrapper.find('.dns-search-input');
        var $clearBtn = $wrapper.find('.dns-search-clear');
        var $tabContent = $wrapper.closest('.dns-tab-content');

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

            // First, hide all parent sections and checkboxes
            $tabContent.find('.dns-location-parent').hide();
            $tabContent.find('.dns-location-children').hide();
            $tabContent.find('.dns-checkbox').hide();

            if (query.length === 0) {
                // If search is empty, show all parents and collapse children
                $tabContent.find('.dns-location-parent').show().removeClass('active');
                $tabContent.find('.dns-location-children').slideUp(0).removeClass('active');
                
                // Show ALL checkboxes (both orphans and children)
                $tabContent.find('.dns-checkbox').show();
            } else {
                var hasMatches = false;

                // Check children within parent groups
                $tabContent.find('.dns-location-children').each(function() {
                    var $childrenContainer = $(this);
                    var parentID = $childrenContainer.attr('id').replace('dns-children-', '');
                    var $parent = $('.dns-location-parent[data-parent-id="' + parentID + '"]');
                    var childMatchCount = 0;

                    // Filter checkboxes in this children container
                    $childrenContainer.find('.dns-checkbox').each(function() {
                        var labelText = $(this).text().toLowerCase();
                        if (labelText.indexOf(query) > -1) {
                            $(this).show();
                            childMatchCount++;
                            hasMatches = true;
                        }
                    });

                    // If any children matched, show parent and expand children
                    if (childMatchCount > 0) {
                        $parent.show();
                        $childrenContainer.show().addClass('active');
                        $parent.addClass('active');
                    }
                });

                // Check orphan checkboxes (not in dns-location-children)
                $tabContent.find('.dns-checkbox-wrapper').not('.dns-location-children .dns-checkbox-wrapper')
                    .find('.dns-checkbox').each(function() {
                        var labelText = $(this).text().toLowerCase();
                        if (labelText.indexOf(query) > -1) {
                            $(this).show();
                            hasMatches = true;
                        }
                    });
            }
        });

        // --------------------------
        // Clear input when clicking cross
        // --------------------------
        $clearBtn.on('click', function() {
            $input.val('');
            $clearBtn.hide();

            // Reset all parents visibility
            $tabContent.find('.dns-location-parent').show().removeClass('active');
            
            // Hide all children containers (collapsed state) with no animation
            $tabContent.find('.dns-location-children').slideUp(0).removeClass('active');
            
            // Show ALL checkboxes (both orphans and those inside children containers)
            $tabContent.find('.dns-checkbox').show();

            $input.focus();
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
        const $checkboxes = $(this).closest('.dns-tab-content').find('.dns-checkbox:visible input[type="checkbox"]');
        $checkboxes.prop('checked', true).closest('.dns-checkbox').addClass('dns-checked');
    });

    $(document).on('click', '.dns-deselect-all', function() {
        const $checkboxes = $(this).closest('.dns-tab-content').find('.dns-checkbox:visible input[type="checkbox"]');
        $checkboxes.prop('checked', false).closest('.dns-checkbox').removeClass('dns-checked');
    });

    /**
     * ---------------------------
     * SHOW SELECTED / SHOW ALL TOGGLE
     * ---------------------------
     */
    $(document).on('click', '.dns-show-selected', function() {
        const $btn = $(this).find('.dns-show-selected-text');
        const $tabContent = $(this).closest('.dns-tab-content');

        if ($btn.text().includes('Show Selected')) {
            // Hide parent groups with no selected children
            $tabContent.find('.dns-location-children').each(function() {
                var $childrenContainer = $(this);
                var parentID = $childrenContainer.attr('id').replace('dns-children-', '');
                var $parent = $('.dns-location-parent[data-parent-id="' + parentID + '"]');
                var selectedCount = 0;

                // Count selected in this group
                $childrenContainer.find('input[type="checkbox"]:checked').each(function() {
                    selectedCount++;
                });

                if (selectedCount > 0) {
                    $parent.show();
                    $childrenContainer.show();
                    // Hide non-selected children
                    $childrenContainer.find('.dns-checkbox').each(function() {
                        if (!$(this).find('input[type="checkbox"]').is(':checked')) {
                            $(this).hide();
                        }
                    });
                } else {
                    $parent.hide();
                    $childrenContainer.hide();
                }
            });

            // Handle orphan checkboxes
            $tabContent.find('.dns-checkbox-wrapper').not('.dns-location-children .dns-checkbox-wrapper')
                .find('.dns-checkbox').each(function() {
                    if (!$(this).find('input[type="checkbox"]').is(':checked')) {
                        $(this).hide();
                    }
                });

            $btn.text('Show All');
        } else {
            // Show all
            $tabContent.find('.dns-location-parent').show();
            $tabContent.find('.dns-checkbox').show();
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

    // Toggle children and arrow on parent click
    $(document).on('click', '.dns-location-parent', function() {
        var parentID = $(this).data('parent-id');
        var children = $('#dns-children-' + parentID);

        // Slide toggle children
        children.slideToggle(200);

        // Toggle active class for arrow rotation
        $(this).toggleClass('active');
    });

});

jQuery(document).ready(function ($) {

    function enhanceDnsNotifications() {

        $('.bs-item-wrap .notification-content span').each(function () {

            var $span = $(this);
            var text  = $span.html();

            // Only process DNS notifications
            if (text.indexOf('dns_unsubscribe=1') === -1) {
                return;
            }

            // Extract URLs
            var urls = text.match(/https?:\/\/[^\s]+/g);
            if (!urls || urls.length < 2) {
                return;
            }

            var listingUrl     = urls[0];
            var unsubscribeUrl = urls[1];

            // Clean message text
            var cleanedText = text
                .replace(listingUrl, '')
                .replace(unsubscribeUrl, '')
                .replace(/\s+/g, ' ')
                .trim();

            // Build enhanced markup
            var html =
                '<div class="dns-notification">' +
                    '<p>' + cleanedText + '</p>' +
                    '<div class="dns-notification-actions">' +
                        '<a href="' + listingUrl + '" class="dns-btn dns-btn-view">View Listing</a>' +
                        '<a href="' + unsubscribeUrl + '" class="dns-btn dns-btn-unsub">Unsubscribe</a>' +
                    '</div>' +
                '</div>';

            $span.html(html);
        });

        // Now remove outer <a> wrapping the span to avoid nested links
        $('.bs-item-wrap .notification-content').each(function () {

            var $content = $(this);
            var $outerLink = $content.find('> a');

            // Only target DNS notifications (must contain our dns-notification)
            if ($outerLink.find('.dns-notification').length === 0) {
                return;
            }

            // Move span content outside the anchor
            var $spanContent = $outerLink.find('span').contents();

            // Remove the outer <a> but keep its content
            $outerLink.replaceWith($spanContent);

        });

    }

    // Run once on page load
    enhanceDnsNotifications();

    // If BuddyBoss loads notifications via AJAX, re-run
    $(document).ajaxComplete(function () {
        enhanceDnsNotifications();
    });

});
