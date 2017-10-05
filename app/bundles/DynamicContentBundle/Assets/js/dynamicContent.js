/** DynamicContentBundle **/
Mautic.dynamicContentOnLoad = function (container, response) {
    if (typeof container !== 'object') {
        if (mQuery(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'dynamicContent');
        }
    }

    var availableFilters = mQuery('div.dwc-filter').find('select[data-mautic="available_filters"]');
    Mautic.activateChosenSelect(availableFilters, false);

    Mautic.leadlistOnLoad('div.dwc-filter');
};

Mautic.standardDynamicContentUrl = function(options) {
    if (!options) {
        return;
    }

    var url = options.windowUrl;
    if (url) {
        var editDynamicContentKey = '/dwc/edit/dynamicContentId';
        var previewDynamicContentKey = '/dwc/preview/dynamicContentId';
        if (url.indexOf(editDynamicContentKey) > -1 ||
            url.indexOf(previewDynamicContentKey) > -1) {
            options.windowUrl = url.replace('dynamicContentId', mQuery('#campaignevent_properties_dynamicContent').val());
        }
    }

    return options;
};

Mautic.disabledDynamicContentAction = function(opener) {
    if (typeof opener == 'undefined') {
        opener = window;
    }

    var dynamicContent = opener.mQuery('#campaignevent_properties_dynamicContent').val();

    var disabled = dynamicContent === '' || dynamicContent === null;

    opener.mQuery('#campaignevent_properties_editDynamicContentButton').prop('disabled', disabled);
};

if (typeof MauticIsDwcReady === 'undefined') {
    var MauticIsDwcReady = true;

    if (
        document.readyState === "complete" ||
        !(document.readyState === "loading" || document.documentElement.doScroll)
    ) {
        Mautic.dynamicContentOnLoad();
    } else {
        document.addEventListener("DOMContentLoaded", Mautic.dynamicContentOnLoad);
    }
}
