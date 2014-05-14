//ApiBundle
Mautic.clientOnLoad = function (container) {
    if ($(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'client');
    }
};