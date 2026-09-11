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

(function () {
    const EVENT_ALREADY_USED_ID = 'ALREADY_USED_ID';
    const EVENT_CHECK_TAB_ID = 'CHECK_TAB_ID';
    const MAUTIC_TAB_KEY = 'mautic-tab-id';
    const TAB_DATA = 'mautic-tab-initialized';

    const channel = globalThis.BroadcastChannel ? new BroadcastChannel('remember-active-tabs') : null;
    let fallbackStorageIdCounter = 0;

    /**
     * Contains keys to the session or local storage to store #href data for each tab.
     * @type {string[]}
     */
    let storageKeys = [];

    const tabStorage = {
        setItem: function (key, value) {
            sessionStorage.setItem(key, value);
            localStorage.setItem(key, value);
        },
        getItem: function (key) {
            if (sessionStorage.getItem(key)) {
                return sessionStorage.getItem(key);
            }

            return localStorage.getItem(key);
        },
        cleanLocalStorage: function () {
            storageKeys.forEach(function (storageKey, index) {
                // If current tab is the last opened for the page, then leave the "saved tabs" as is.
                if (localStorage.getItem(tabId(index)) === storageKeys[index]) {
                    return;
                }

                // Otherwise cleanup the localStorage. Current page will use the session storage,
                // and in case user changes the tab on this page, it will be written to localStorage.
                localStorage.removeItem(storageKeys[index]);
            });
        }
    };

    // Generate a unique storage ID.
    const generateStorageId = (index) => {
        const randomUUID = globalThis.crypto?.randomUUID?.();
        if (randomUUID) {
            return `${randomUUID}_${index}`;
        }

        if (globalThis.crypto?.getRandomValues) {
            const randomValues = new Uint8Array(16);
            globalThis.crypto?.getRandomValues(randomValues);

            randomValues[6] = (randomValues[6] & 0x0f) | 0x40;
            randomValues[8] = (randomValues[8] & 0x3f) | 0x80;

            const hexValues = Array.from(randomValues, (value) => value.toString(16).padStart(2, '0'));

            const uuid = [
                hexValues.slice(0, 4).join(''),
                hexValues.slice(4, 6).join(''),
                hexValues.slice(6, 8).join(''),
                hexValues.slice(8, 10).join(''),
                hexValues.slice(10).join(''),
            ].join('-');

            return `${uuid}_${index}`;
        }

        fallbackStorageIdCounter += 1;

        const currentTime = Date.now().toString(36);
        const performanceTime = globalThis.performance?.now?.().toString().replace('.', '-') ?? '0';

        return `${currentTime}-${performanceTime}-${fallbackStorageIdCounter}_${index}`;
    };

    /**
     * Generate the per-page hash to store opened tab data.
     * @param {number} index
     * @returns {string}
     */
    const tabId = function (index) {
        return `${MAUTIC_TAB_KEY}-${globalThis.location.pathname}-${index}`;
    };

    // Generate new tab ID if one was already used.
    channel?.addEventListener('message', (event) => {
        if (event.data.type !== EVENT_ALREADY_USED_ID) {
            return;
        }

        if (event.data.storageKey !== storageKeys[event.data.index]) {
            return;
        }

        const openedTab = tabStorage.getItem(storageKeys[event.data.index]);

        // If storage contains already closed/invalid tab.
        if (!openedTab) {
            return;
        }

        while (event.data.storageKey === storageKeys[event.data.index]) {
            storageKeys[event.data.index] = generateStorageId(event.data.index);
        }

        // Store new tab storage key for current session and also "latest used" in localStorage.
        tabStorage.setItem(tabId(event.data.index), storageKeys[event.data.index]);
        tabStorage.setItem(storageKeys[event.data.index], openedTab);
    });

    // Check if tab id is already used. Happens on duplicate browser tab.
    channel?.addEventListener('message', (event) => {
        if (event.data.type !== EVENT_CHECK_TAB_ID) {
            return;
        }

        if (event.data.storageKey !== storageKeys[event.data.index]) {
            return;
        }

        channel.postMessage({
            type: EVENT_ALREADY_USED_ID,
            storageKey: storageKeys[event.data.index],
            index: event.data.index,
        });
    });

    // Cleanup localStorage on refresh or tab close.
    globalThis.addEventListener('beforeunload', () => {
        tabStorage.cleanLocalStorage();

        channel?.close();
    });

    /**
     * Remember the last active tab for each tab list on the page.
     */
    Mautic.rememberActiveTabs = function() {
        mQuery('.nav-tabs').each(function(index) {
            // Using index would have nasty effects when tabs, with different tab count, are loaded asynchronously somewhere on the page.
            const $navTabs = mQuery(this);
            const mauticTabKey = tabId(index);

            // Prevent "initializing" remember functionality for tab with each AJAX request.
            if (mauticTabKey === $navTabs.data(TAB_DATA)) {
                return;
            }

            $navTabs.data(TAB_DATA, mauticTabKey);

            if (tabStorage.getItem(mauticTabKey)) {
                // Last opened tab on this page (either from session or from local storage)
                storageKeys[index] = tabStorage.getItem(mauticTabKey);

                channel?.postMessage({
                    type: EVENT_CHECK_TAB_ID,
                    storageKey: storageKeys[index],
                    index: index, // Index must be passed to generateStorageId only.
                });
            } else {
                storageKeys[index] = generateStorageId(index);
                tabStorage.setItem(mauticTabKey, storageKeys[index]);
            }

            const activeTab = tabStorage.getItem(storageKeys[index]);
            if (activeTab) {
                // With the current tab key get an active tab.
                const $tab = $navTabs.find('a[href="' + activeTab + '"]');
                if ($tab.length) {
                    $tab.tab('show');
                }
            } else {
                // Or store the default, so the script will not end up with NULL value.
                const href = $navTabs.find('.active a[data-toggle="tab"]').attr('href');
                tabStorage.setItem(storageKeys[index], href);
            }

            $navTabs.find('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                tabStorage.setItem(storageKeys[index], mQuery(e.target).attr('href'));
            });
        });
    };
})();

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
    Mautic.rememberActiveTabs();
    Mautic.initTabsScroll();
});

// Re-initialize on every AJAX complete
mQuery(document).ajaxComplete(function(event, xhr, settings) {
    Mautic.rememberActiveTabs();
    Mautic.initTabsScroll();
});
