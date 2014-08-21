//AssetBundle
Mautic.assetOnLoad = function (container) {
    // if (mQuery(container + ' #list-search').length) {
    //     Mautic.activateSearchAutocomplete('list-search', 'asset.asset');
    // }

    if (mQuery(container + ' form[name="asset"]').length) {
       Mautic.activateCategoryLookup('asset', 'asset');
    }
};
