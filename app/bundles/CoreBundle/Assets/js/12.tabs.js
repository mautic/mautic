Mautic.overflowNavOptions = {
    "parent": ".nav-overflow-tabs",
    "more": Mautic.translate('mautic.core.tabs.more')
};

/**
 * Toggle a tab based on published status
 *
 * @param el
 */
Mautic.toggleTabPublished = function(el) {
    if (mQuery(el).val() === "1" && mQuery(el).prop('checked')) {
        Mautic.publishTab(el);
    } else {
        Mautic.unpublishTab(el);
    }
}

/**
 * Publish a tab
 *
 * @param tab
 */
Mautic.publishTab = function(tab) {
    mQuery('a[href="#'+Mautic.getTabId(tab)+'"]').find('.fa').removeClass('text-secondary').addClass('text-success');
};

/**
 * Unpublish a tab
 *
 * @param tab
 */
Mautic.unpublishTab = function(tab) {
    mQuery('a[href="#'+Mautic.getTabId(tab)+'"]').find('.fa').removeClass('text-success').addClass('text-secondary');
};

/**
 * Get the tab ID from the given element
 *
 * @param tab
 * @returns {*}
 */
Mautic.getTabId = function(tab) {
    if (!mQuery(tab).hasClass('tab-pane')) {
        tab = mQuery(tab).closest('.tab-pane');
    }

    return mQuery(tab).attr('id');
};

/**
 *
 * @param tabs
 * @param options
 */
Mautic.activateOverflowTabs = function(tabs, options) {
    if (!options) {
        options = {};
    }

    var localOptions = Mautic.overflowNavOptions;

    mQuery.extend(localOptions, options);
    mQuery(tabs).overflowNavs(localOptions);

    var resizeMe = function(tabs, options) {
        mQuery(window).on('resize', {tabs: tabs, options: options},
            function (event) {
                mQuery(event.data.tabs).overflowNavs(event.data.options);
            }
        );
    };

    resizeMe(tabs, localOptions);
};

/**
 * Activate sortable tabs
 * @param tabs
 */
Mautic.activateSortableTabs = function(tabs) {
    mQuery(tabs).sortable(
        {
            container: 'ul.nav',
            axis: mQuery(tabs).hasClass('tabs-right') || mQuery(tabs).hasClass('tabs-left') ? 'y' : 'x',
            stop: function (e, ui) {
                var action = mQuery(tabs).attr('data-sort-action');
                mQuery.ajax({
                    type: "POST",
                    url: action,
                    data: mQuery(tabs).sortable("serialize", {attribute: 'data-tab-id'})
                });
            }
        }
    );
};

/**
 * Activate hover delete buttons
 *
 * @param container
 */
Mautic.activateTabDeleteButtons = function(container) {
    mQuery(container + " .nav.nav-deletable>li a").each(
        function() {
            Mautic.activateTabDeleteButton(this);
        }
    );
};

/**
 * Activate hover and click for tab deletes
 *
 * @param tab
 */
Mautic.activateTabDeleteButton = function(tab) {
    var btn = mQuery('<span class="btn btn-danger btn-xs btn-delete pull-right hide"><i class="ri-close-line"></i></span>')
        .on('click',
            function() {
                return Mautic.deleteTab(btn)
            }
        ).appendTo(tab);

    mQuery(tab).hover(
        function() {
            mQuery(btn).removeClass('hide');
        },
        function () {
            mQuery(btn).addClass('hide');
        }
    );
};

/**
 * Delete a tab
 *
 * @param tab
 */
Mautic.deleteTab = function(deleteBtn) {
    var tab = mQuery(deleteBtn).closest('li');
    var tabContent = mQuery(deleteBtn).closest('a').attr('href');

    var parent = mQuery(tab).closest('ul');
    var wasActive = (mQuery(tab.hasClass('active')));

    var action = mQuery(parent).attr('data-delete-action');
    if (action) {
        var success = false;
        mQuery.ajax({
            url: action,
            type: 'POST',
            dataType: "json",
            data: {tab: tabContent},
            success: function (response) {
                if (response && response.success) {
                    mQuery(tab).remove();
                    mQuery(tabContent).remove();

                    if (wasActive) {
                        mQuery(parent).find('li:first a').click();
                    }

                    if (!mQuery(parent).find('li').length) {
                        mQuery('.tab-content .placeholder').removeClass('hide');
                    }
                } else {
                    Mautic.stopIconSpinPostEvent();
                }
            }
        });
    } else {
        mQuery(tab).remove();
        mQuery(tabContent).remove();

        if (wasActive) {
            mQuery(parent).find('li:first a').click();
        }

        if (!mQuery(parent).find('li').length) {
            mQuery('.tab-content .placeholder').removeClass('hide');
        }
    }

    return false;
};

// Initialize the Tabs Scroll functionality
Mautic.initTabsScroll = function() {
    mQuery('.nav-tabs').each(function() {
        var $navTabs = mQuery(this);

        // Avoid initializing the same nav-tabs multiple times
        if ($navTabs.parent().hasClass('nav-tabs-wrapper')) {
            return; // Already initialized
        }

        // Create wrapper
        var $navTabsWrapper = mQuery('<div class="nav-tabs-wrapper"></div>');

        // Wrap the nav-tabs with the wrapper
        $navTabs.wrap($navTabsWrapper);

        // After wrapping, update the reference
        $navTabsWrapper = $navTabs.parent('.nav-tabs-wrapper');

        // Append scroll buttons with type="button" and specified icons
        var $leftBtn = mQuery('<button type="button" class="scroll-btn left-btn"><i class="ri-arrow-left-wide-line"></i></button>');
        var $rightBtn = mQuery('<button type="button" class="scroll-btn right-btn"><i class="ri-arrow-right-wide-line"></i></button>');

        $navTabsWrapper.append($leftBtn);
        $navTabsWrapper.append($rightBtn);

        var scrollAmount = 150;

        // Function to update button states and visibility
        function updateButtons() {
            var scrollLeft = $navTabs.scrollLeft();
            var maxScrollLeft = $navTabs[0].scrollWidth - $navTabs[0].clientWidth;

            if (maxScrollLeft > 0) {
                // Tabs overflow the container, show buttons
                $navTabsWrapper.addClass('show-scroll-buttons');
            } else {
                // No overflow, hide buttons
                $navTabsWrapper.removeClass('show-scroll-buttons');
            }

            // Update button disabled state
            $leftBtn.prop('disabled', scrollLeft <= 0);
            $rightBtn.prop('disabled', scrollLeft >= (maxScrollLeft - 1));
        }

        // Scroll Left
        $leftBtn.on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $navTabs.animate({ scrollLeft: $navTabs.scrollLeft() - scrollAmount }, 300, updateButtons);
        });

        // Scroll Right
        $rightBtn.on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $navTabs.animate({ scrollLeft: $navTabs.scrollLeft() + scrollAmount }, 300, updateButtons);
        });

        // Update buttons on scroll and resize
        $navTabs.on('scroll', updateButtons);
        mQuery(window).on('resize', debounce(updateButtons, 100));

        // Initial button state
        updateButtons();
    });
};

// Debounce function to limit how often a function can fire
function debounce(func, wait) {
    let timeout;
    return function() {
        clearTimeout(timeout);
        timeout = setTimeout(func, wait);
    };
}

// Initialize on document ready
mQuery(document).ready(function() {
    Mautic.initTabsScroll();
});

// Re-initialize on every AJAX complete
mQuery(document).ajaxComplete(function(event, xhr, settings) {
    Mautic.initTabsScroll();
});