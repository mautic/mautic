//ApiBundle
Mautic.clientOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'api.client');
    }
};