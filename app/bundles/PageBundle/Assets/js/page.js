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

    if (mQuery(container + ' #page_template').length) {
        Mautic.toggleBuilderButton(mQuery('#page_template').val() == '');
    }
};

Mautic.pageOnUnload = function(id) {
    if (id === '#app-content') {
        delete Mautic.pageViewsBarChartObject;
        delete Mautic.pageReturningVisitsPie;
        delete Mautic.pageTimePie;
    }
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

Mautic.getPageAbTestWinnerForm = function(abKey) {
    if (abKey && mQuery(abKey).val() && mQuery(abKey).closest('.form-group').hasClass('has-error')) {
        mQuery(abKey).closest('.form-group').removeClass('has-error');
        if (mQuery(abKey).next().hasClass('help-block')) {
            mQuery(abKey).next().remove();
        }
    }

    Mautic.activateLabelLoadingIndicator('page_variantSettings_winnerCriteria');

    var pageId = mQuery('#page_sessionId').val();
    var query  = "action=page:getAbTestForm&abKey=" + mQuery(abKey).val() + "&pageId=" + pageId;

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

            Mautic.removeLabelLoadingIndicator();

        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
            spinner.remove();
        },
        complete: function () {
            Mautic.removeLabelLoadingIndicator();
        }
    });
};