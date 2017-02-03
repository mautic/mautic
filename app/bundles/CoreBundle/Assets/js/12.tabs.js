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
    mQuery('a[href="#'+Mautic.getTabId(tab)+'"]').find('.fa').removeClass('text-muted').addClass('text-success');
};

/**
 * Unpublish a tab
 *
 * @param tab
 */
Mautic.unpublishTab = function(tab) {
    mQuery('a[href="#'+Mautic.getTabId(tab)+'"]').find('.fa').removeClass('text-success').addClass('text-muted');
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