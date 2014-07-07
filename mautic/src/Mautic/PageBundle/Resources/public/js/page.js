//PageBundle
Mautic.pageOnLoad = function (container) {
    if (mQuery(container + ' form[name="page"]').length) {
        mQuery('.bundle-main').addClass('fullpanel');
    }

    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'page.page');
    }

    if (mQuery(container + ' form[name="page"]').length) {
        //active lookups
        if (mQuery('#page_parent_lookup').length) {
            var pages = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: {
                    url: mauticAjaxUrl + "?action=page:pageList&filter=%QUERY",
                    ajax: {
                        beforeSend: function () {
                            MauticVars.showLoadingBar = false;
                        }
                    }
                },
                ttl: 1,
                limit: 10
            });
            pages.initialize();

            mQuery("#page_parent_lookup").typeahead(
                {
                    hint: true,
                    highlight: true,
                    minLength: 2
                },
                {
                    name: 'page_parent',
                    displayKey: 'label',
                    source: pages.ttAdapter()
                }).on('typeahead:selected', function (event, datum) {
                    mQuery("#page_parent").val(datum["value"]);
                }).on('typeahead:autocompleted', function (event, datum) {
                    mQuery("#page_parent").val(datum["value"]);
                }).on('keypress', function (event) {
                    if ((event.keyCode || event.which) == 13) {
                        mQuery('#page_parent_lookup').typeahead('close');
                    }
                });
        }

        if (mQuery('#page_category_lookup').length) {
            var cats = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticAjaxUrl + "?action=page:categoryList",
                    ajax: {
                        beforeSend: function () {
                            MauticVars.showLoadingBar = false;
                        }
                    }
                },
                remote: {
                    url: mauticAjaxUrl + "?action=page:categoryList&filter=%QUERY",
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

            mQuery("#page_category_lookup").typeahead(
                {
                    hint: true,
                    highlight: true,
                    minLength: 2
                },
                {
                    name: 'page_category',
                    displayKey: 'label',
                    source: cats.ttAdapter()
                }).on('typeahead:selected', function (event, datum) {
                    mQuery("#page_category").val(datum["value"]);
                }).on('typeahead:autocompleted', function (event, datum) {
                    mQuery("#page_category").val(datum["value"]);
                }).on('keypress', function (event) {
                    if ((event.keyCode || event.which) == 13) {
                        mQuery('#page_category_lookup').typeahead('close');
                    }
                });
        }
    }
};

Mautic.pageUnLoad = function() {
    //remove page builder from body
    mQuery('.page-builder').remove();
};

Mautic.pagecategoryOnLoad = function (container) {
    if (mQuery(container + ' form[name="pagecategory"]').length) {
        mQuery('.bundle-main').addClass('fullpanel');
    }

    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'page.category');
    }
};

Mautic.launchPageEditor = function () {
    //append template to the URL
    var src = mQuery('#pageBuilderUrl').val();
    src += '?template=' + mQuery('#page_template').val();

    mQuery('.page-builder iframe').attr('src', src);

    //Append to body to break out of the main panel
    mQuery('.page-builder').appendTo('body');
    //make the panel full screen
    mQuery('.page-builder').addClass('page-builder-active');
    //show it
    mQuery('.page-builder').removeClass('hide');
};

Mautic.closePageEditor = function() {
    mQuery('.page-builder').addClass('hide');
};