/** CategoryBundle **/

Mautic.categoryOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'category');
    }
};

Mautic.activateCategoryLookup = function (formName, bundlePrefix) {
    //activate lookups
    if (mQuery('#'+formName+'_category_lookup').length) {
        var cats = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            prefetch: {
                url: mauticAjaxUrl + "?action=category:categoryList&bundle=" + bundlePrefix,
                ajax: {
                    beforeSend: function () {
                        MauticVars.showLoadingBar = false;
                    }
                }
            },
            remote: {
                url: mauticAjaxUrl + "?action=category:categoryList&bundle=" + bundlePrefix + "filter=%QUERY",
                ajax: {
                    beforeSend: function () {
                        MauticVars.showLoadingBar = false;
                    }
                }
            },
            dupDetector: function (remoteMatch, localMatch) {
                return (remoteMatch.label == localMatch.label);
            },
            ttl: 1,
            limit: 10
        });
        cats.initialize();

        mQuery("#" + formName + "_category_lookup").typeahead(
            {
                hint: true,
                highlight: true,
                minLength: 2
            },
            {
                name: bundlePrefix + '_category',
                displayKey: 'label',
                source: cats.ttAdapter()
            }).on('typeahead:selected', function (event, datum) {
                mQuery("#" + formName + "_category").val(datum["value"]);
            }).on('typeahead:autocompleted', function (event, datum) {
                mQuery("#" + formName + "_category").val(datum["value"]);
            }).on('keypress', function (event) {
                if ((event.keyCode || event.which) == 13) {
                    mQuery('#' + formName + '_category_lookup').typeahead('close');
                }
            });
    }
}