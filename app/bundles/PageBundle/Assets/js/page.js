//PageBundle
Mautic.pageOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'page.page');
    }

    if (mQuery(container + ' .page-stat-charts').length) {
        //details view
        Mautic.renderPageViewsBarChart(container);
        Mautic.renderPageReturningVisitsPie();
        Mautic.renderPageTimePie();
    }
};

Mautic.pageOnUnload = function(id) {
    if (id === '#app-content') {
        delete Mautic.pageViewsBarChartObject;
        delete Mautic.pageReturningVisitsPie;
        delete Mautic.pageTimePie;
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
        .appendTo('.builder-content')
        .load(function () {
            var $this = mQuery(this);
            var contents = $this.contents();
            // here, catch the droppable div and create a droppable widget
            contents.find('.mautic-editable').droppable({
                iframeFix: true,
                drop: function (event, ui) {
                    var instance = mQuery(this).attr("id");
                    var predrop  = mQuery(ui.draggable).data('predrop');
                    if (predrop) {
                        Mautic[predrop](event, ui, instance);
                    } else {
                        var token  = mQuery(ui.draggable).data('token');
                        if (token) {
                            Mautic.insertPageBuilderToken(instance, token);
                        }
                    }
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

    //make the panel full screen
    mQuery('.builder').addClass('builder-active');
    //show it
    mQuery('.builder').removeClass('hide');

    Mautic.pageEditorOnLoad('.builder-panel');
};

Mautic.getPageBuilderEditorInstances = function() {
    return document.getElementById('builder-template-content').contentWindow.CKEDITOR.instances;
};

Mautic.closePageEditor = function() {
    Mautic.stopIconSpinPostEvent();

    mQuery('.builder').addClass('hide');

    //make sure editors have lost focus so the content is updated
    mQuery('#builder-template-content').contents().find('.mautic-editable').each(function (index) {
        mQuery(this).blur();
    });

    setTimeout( function() {
        //kill the draggables
        mQuery('#builder-template-content').contents().find('.mautic-editable').droppable('destroy');
        mQuery("*[data-token]").draggable('destroy');

        //kill the iframe
        mQuery('#builder-template-content').remove();

    }, 3000);
};

Mautic.pageEditorOnLoad = function (container) {
    //activate builder drag and drop
    mQuery(container + " *[data-token]").draggable({
        iframeFix: true,
        iframeId: 'builder-template-content',
        helper: 'clone',
        appendTo: '.builder',
        zIndex: 8000,
        scroll: true,
        scrollSensitivity: 100,
        scrollSpeed: 100,
        cursorAt: {top: 15, left: 15}
    });
};

Mautic.renderPageViewsBarChart = function (container) {
    if (!mQuery('#page-views-chart').length) {
        return;
    }
    chartData = mQuery.parseJSON(mQuery('#page-views-chart-data').text());
    if (typeof chartData.labels === "undefined") {
        return;
    }
    var ctx = document.getElementById("page-views-chart").getContext("2d");
    var options = {
         scaleShowGridLines : false,
         barShowStroke : false,
         barValueSpacing : 1,
         showScale: false,
         tooltipFontSize: 10,
         tooltipCaretSize: 0
    }
    if (typeof Mautic.pageViewsBarChartObject === 'undefined') {
        Mautic.pageViewsBarChartObject = new Chart(ctx).Bar(chartData, options);
    }
};

Mautic.renderPageReturningVisitsPie = function () {
    // Initilize chart only for first time
    if (typeof Mautic.pageReturningVisitsPie === 'object') {
        return;
    }
    var graphData = mQuery.parseJSON(mQuery('#returning-data').text());
    var options = {
        responsive: false,
        tooltipFontSize: 10
    };
    graphData = Mautic.emulateNoDataForPieChart(graphData);
    var ctx = document.getElementById("returning-rate").getContext("2d");
    Mautic.pageReturningVisitsPie = new Chart(ctx).Pie(graphData, options);
};

Mautic.renderPageTimePie = function () {
    // Initilize chart only for first time
    if (typeof Mautic.pageTimePie === 'object') {
        return;
    }
    var element = mQuery('#time-rate');
    var options = {
        responsive: false,
        tooltipFontSize: 10,
        tooltipTemplate: "<%if (label){%><%}%><%= value %>x <%=label%>"};
    var timesOnSiteData = mQuery.parseJSON(mQuery('#times-on-site-data').text());
    timesOnSiteData = Mautic.emulateNoDataForPieChart(timesOnSiteData);
    var ctx = document.getElementById("time-rate").getContext("2d");
    Mautic.pageTimePie = new Chart(ctx).Pie(timesOnSiteData, options);
};

Mautic.showPageBuilderTokenExternalLinkModal = function (event, ui, editorId) {
    var token  = mQuery(ui.draggable).data('token');
    mQuery('#ExternalLinkModal input[name="editor"]').val(editorId);
    mQuery('#ExternalLinkModal input[name="token"]').val(token);

    //append the modal to the builder or else it won't display
    mQuery('#ExternalLinkModal').appendTo('body');

    mQuery('#ExternalLinkModal').modal('show');
};

Mautic.insertPageBuilderTokenExternalUrl = function () {
    var editorId = mQuery('#ExternalLinkModal input[name="editor"]').val();
    var token    = mQuery('#ExternalLinkModal input[name="token"]').val();
    var url      = mQuery('#ExternalLinkModal input[name="link"]').val();

    token = token.replace("%url%", url);

    Mautic.insertPageBuilderToken(editorId, token);
};

Mautic.insertPageBuilderToken = function(editorId, token) {
    var editor = Mautic.getPageBuilderEditorInstances();
    editor[editorId].insertText(token);
    mQuery('#ExternalLinkModal').modal('hide');

    mQuery('#ExternalLinkModal input[name="link"]').val('');
};

Mautic.togglePageContentMode = function (el) {
    var builder = (mQuery(el).val() === '0') ? false : true;

    if (builder) {
        mQuery('#customHtmlContainer').addClass('hide');
        mQuery('#builderHtmlContainer').removeClass('hide');
        mQuery('#metaDescriptionContainer').removeClass('hide');
    } else {
        mQuery('#customHtmlContainer').removeClass('hide');
        mQuery('#builderHtmlContainer').addClass('hide');
        mQuery('#metaDescriptionContainer').addClass('hide');
    }
};

Mautic.getPageAbTestWinnerForm = function(abKey) {
    if (abKey && mQuery(abKey).val() && mQuery(abKey).closest('.form-group').hasClass('has-error')) {
        mQuery(abKey).closest('.form-group').removeClass('has-error');
        if (mQuery(abKey).next().hasClass('help-block')) {
            mQuery(abKey).next().remove();
        }
    }

    var labelSpinner = mQuery("label[for='page_variantSettings_winnerCriteria']");
    var spinner = mQuery('<i class="fa fa-fw fa-spinner fa-spin"></i>');
    labelSpinner.append(spinner);

    var pageId = mQuery('#page_sessionId').val();

    var query = "action=page:getAbTestForm&abKey=" + mQuery(abKey).val() + "&pageId=" + pageId;

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                if (mQuery('#page_variantSettings_properties').length) {
                    mQuery('#page_variantSettings_properties').replaceWith(response.html);
                } else {
                    mQuery('#page_variantSettings').append(response.html);
                }

                if (response.html != '') {
                    Mautic.onPageLoad('#page_variantSettings_properties', response);
                }
            }
            spinner.remove();
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
            spinner.remove();
        }
    });
};