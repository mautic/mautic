//AssetBundle
Mautic.assetOnLoad = function (container) {
    if (mQuery(container + ' form[name="asset"]').length) {
       Mautic.activateCategoryLookup('asset', 'asset');
    }
};
