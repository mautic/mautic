/** DynamicContentBundle **/
Mautic.dynamicContentOnLoad = function (container, response) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'dynamicContent');
    }
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
    if (typeof opener === 'undefined') {
        opener = window;
    }

    var dynamicContent = opener.mQuery('#campaignevent_properties_dynamicContent').val();

    var disabled = dynamicContent === '' || dynamicContent === null;

    opener.mQuery('#campaignevent_properties_editDynamicContentButton').prop('disabled', disabled);
};

if (typeof MauticIsDwcReady === 'undefined') {
    var MauticIsDwcReady = true;
    // Handler when the DOM is fully loaded
    var callback = function(){
        var availableFilters = mQuery('div.dwc-filter').find('select[data-mautic="available_filters"]');
        Mautic.activateChosenSelect(availableFilters, false);

        Mautic.leadlistOnLoad('div.dwc-filter');
    };

    if (
        document.readyState === "complete" ||
        !(document.readyState === "loading" || document.documentElement.doScroll)
    ) {
        callback();
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }

}
