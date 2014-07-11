//PageBundle
Mautic.pageOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'page.page');
    }

    if (mQuery(container + ' form[name="page"]').length) {
        mQuery('.bundle-main').addClass('fullpanel');

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
    var src = mQuery('#pageBuilderUrl').val();
    src += '?template=' + mQuery('#page_template').val();

    var builder = mQuery("<iframe />", {
        css: {
            margin: "0",
            padding: "0",
            border: "none",
            width: "100%",
            height: "100%"
        },
        id: "builder-template-content"
    })
        .attr('src', src)
        .appendTo('.page-builder-content')
        .load(function () {
            var $this = mQuery(this);
            var contents = $this.contents();
            // here, catch the droppable div and create a droppable widget
            contents.find('.mautic-editable').droppable({
                iframeFix: true,
                drop: function (event, ui) {
                    var instance = mQuery(this).attr("id");
                    var editor   = document.getElementById('builder-template-content').contentWindow.CKEDITOR.instances;
                    var token = mQuery(ui.draggable).find('input.page-token').val();
                    editor[instance].insertText(token);
                    mQuery(this).removeClass('over-droppable');
                },
                over: function (e, ui) {
                    mQuery(this).addClass('over-droppable');
                },
                out: function (e, ui) {
                    mQuery(this).removeClass('over-droppable');
                }
            });
        });

    //Append to body to break out of the main panel
    mQuery('.page-builder').appendTo('body');
    //make the panel full screen
    mQuery('.page-builder').addClass('page-builder-active');
    //show it
    mQuery('.page-builder').removeClass('hide');

    Mautic.pageEditorOnLoad('.page-builder-panel');
};

Mautic.closePageEditor = function() {
    mQuery('.page-builder').addClass('hide');

    //make sure editors have lost focus so the content is updated
    mQuery('#builder-template-content').contents().find('.mautic-editable').each(function (index) {
        mQuery(this).blur();
    });

    setTimeout( function() {
        //kill the draggables
        mQuery('#builder-template-content').contents().find('.mautic-editable').droppable('destroy');
        mQuery("ul.draggable li").draggable('destroy');

        //kill the iframe
        mQuery('#builder-template-content').remove();

        //move the page builder back into form
        mQuery('.page-builder').appendTo('.bundle-main-inner-wrapper');
    }, 3000);
};

Mautic.pageEditorOnLoad = function (container) {
    //activate builder drag and drop
    mQuery(container + " ul.draggable li").draggable({
        iframeFix: true,
        iframeId: 'builder-template-content',
        helper: function() {
            return mQuery('<div><i class="fa fa-lg fa-crosshairs"></i></div>');
        },
        appendTo: '.page-builder',
        zIndex: 8000,
        scroll: true,
        scrollSensitivity: 100,
        scrollSpeed: 100,
        cursorAt: {top: 15, left: 15}
    });
};