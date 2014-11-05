//PageBundle
Mautic.pageOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'page.page');
    }

    if (mQuery(container + ' form[name="page"]').length) {
       Mautic.activateCategoryLookup('page', 'page');
    }

    Mautic.renderPageViewsBarChart(container);
    Mautic.renderPageReturningVisitsPie();
    Mautic.renderPageTimePie();
};

Mautic.pageUnLoad = function() {
    //remove page builder from body
    mQuery('.page-builder').remove();
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
    Mautic.stopIconSpinPostEvent();

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
        helper: 'clone',
        appendTo: '.page-builder',
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
    if (typeof chartData.labels === "undefined" || typeof chartData.values === "undefined") {
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
    var data = {
        labels: chartData.labels,
        datasets: [
            {
                fillColor: "#00b49c",
                highlightFill: "#028473",
                data: chartData.values
            }
        ]
    };
    if (typeof Mautic.pageViewsBarChartObject === 'undefined') {
        Mautic.pageViewsBarChartObject = new Chart(ctx).Bar(data, options);
    }
};

Mautic.renderPageReturningVisitsPie = function () {
    // Initilize chart only for first time
    if (typeof Mautic.pageReturningVisitsPie === 'object') {
        return;
    }
    var element = mQuery('#returning-rate');
    var total = +element.attr('data-hit-count');
    var unique = +element.attr('data-unique-hit-count');
    var returning = total - unique;
    var options = {
        responsive: false,
        tooltipFontSize: 10,
        tooltipTemplate: "<%if (label){%><%}%><%= value %>% <%=label%>"};
    if (!total) {
        return;
    }
    var data = [
        {
            value: Math.round(returning / total * 100),
            color:"#4E5D9D",
            highlight: "#353F6A",
            label: "Returning"
        },
        {
            value: Math.round(unique / total * 100),
            color: "#00b49c",
            highlight: "#007A69",
            label: "New"
        }
    ];
    var ctx = document.getElementById("returning-rate").getContext("2d");
    Mautic.pageReturningVisitsPie = new Chart(ctx).Pie(data, options);
}

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
    var ctx = document.getElementById("time-rate").getContext("2d");
    Mautic.pageTimePie = new Chart(ctx).Pie(timesOnSiteData, options);
}
