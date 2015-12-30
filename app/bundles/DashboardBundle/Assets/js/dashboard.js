//DashboardBundle
Mautic.dashboardOnLoad = function (container) {
    Mautic.renderReturnRateDoughnut();
    Mautic.renderClickRateDoughnut();
    Mautic.updateActiveVisitorCount();
    Mautic.initWidgetSorting();

    // Refresh page visits every 5 sec
    Mautic.setModeratedInterval('ActiveVisitorsLoop', 'updateActiveVisitorCount', 5000);
};

Mautic.dashboardOnUnload = function(id) {
    // Trash initialized dashboard vars on app content change.
    mQuery('.jvectormap-tip').remove();
    if (id === '#app-content') {
        delete Mautic.dashboardClickRateDoughnutObject;
        delete Mautic.dashboardReturnRateDoughnutObject;
        delete Mautic.ActiveVisitorsCount;
    }
    Mautic.clearModeratedInterval('ActiveVisitorsLoop');
};

Mautic.widgetOnLoad = function(container, response) {
    if (!response.widgetId) return;
    var widget = mQuery('[data-widget-id=' + response.widgetId + ']');
    var widgetHtml = mQuery(response.widgetHtml);

    // initialize edit button modal again
    widgetHtml.find("*[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
        event.preventDefault();
        Mautic.ajaxifyModal(this, event);
    });

    // Create the new widget wrapper and add it to the 0 position if doesn't exist (probably a new one)
    if (!widget.length) {
        widget = mQuery('<div/>')
            .addClass('widget')
            .attr('data-widget-id', response.widgetId);
        mQuery('#dashboard-widgets').prepend(widget);
    }

    widget.html(widgetHtml)
        .css('width', response.widgetWidth + '%')
        .css('height', response.widgetHeight + '%');
    Mautic.renderCharts(widgetHtml);
    Mautic.renderMaps(widgetHtml);
    Mautic.saveWidgetSorting();
}

Mautic.initWidgetSorting = function () {
    var widgetsWrapper = mQuery('#dashboard-widgets');
    widgetsWrapper.sortable({
        handle: '.panel-heading',
        placeholder: 'sortable-placeholder',
        items: '.widget',
        opacity: 0.9,
        stop: function() {
            Mautic.saveWidgetSorting();
        },
        start: function( event, ui ) {
            console.log(ui.item);
            // Adjust placeholder's size according to dragging element size
            ui.placeholder.css(ui.item.children().css(['width', 'height']));
        }
    }).disableSelection();
}

Mautic.saveWidgetSorting = function () {
    var widgetsWrapper = mQuery('#dashboard-widgets');
    var widgets = widgetsWrapper.children();
    var ordering = [];
    widgets.each(function(index, value) { 
        ordering.push(mQuery(this).attr('data-widget-id')); 
    });
    Mautic.ajaxActionRequest('dashboard:updateWidgetOrdering', {'ordering': ordering}, function(response) {
        // @todo handle errors
    });
}

Mautic.renderReturnRateDoughnut = function () {
    // Initilize chart only for first time
    if (typeof Mautic.dashboardReturnRateDoughnutObject === 'object') {
        return;
    }
    var element = mQuery('#return-rate');
    var visitCount = +element.attr('data-visit-count');
    var returnCount = +element.attr('data-return-count');
    var options = {percentageInnerCutout: 65, responsive: false}
    var data = [
        {
            value: returnCount,
            color:"#4E5D9D",
            highlight: "#353F6A",
            label: "Returned"
        },
        {
            value: visitCount - returnCount,
            color: "#efeeec",
            highlight: "#EBEBEB",
            label: "Unique"
        }
    ];
    data = Mautic.emulateNoDataForPieChart(data);
    var ctx = document.getElementById("return-rate").getContext("2d");
    Mautic.dashboardReturnRateDoughnutObject = new Chart(ctx).Doughnut(data, options);
}

Mautic.renderClickRateDoughnut = function () {
    // Initilize chart only for first time
    if (typeof Mautic.dashboardClickRateDoughnutObject === 'object') {
        return;
    }
    var element = mQuery('#click-rate');
    var readCount = +element.attr('data-sent-count');
    var clickCount = +element.attr('data-click-count');
    var options = {percentageInnerCutout: 65, responsive: false}
    var data = [
        {
            value: clickCount,
            color:"#35B4B9",
            highlight: "#227276",
            label: "Clicked"
        },
        {
            value: readCount - clickCount,
            color: "#efeeec",
            highlight: "#EBEBEB",
            label: "Not clicked"
        }
    ];
    data = Mautic.emulateNoDataForPieChart(data);
    var ctx = document.getElementById("click-rate").getContext("2d");
    Mautic.dashboardClickRateDoughnutObject = new Chart(ctx).Doughnut(data, options);
}

Mautic.updateActiveVisitorCount = function () {
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: "action=dashboard:viewingVisitors",
        dataType: "json",
        success: function (response) {
            if (response.success) {
                var element = mQuery('#active-visitors');
                element.text(response.viewingVisitors);
                if (response.viewingVisitors != Mautic.ActiveVisitorsCount) {
                    var color = '#34D43B';
                    if (Mautic.ActiveVisitorsCount > response.viewingVisitors) {
                        color = '#BC2525';
                    }
                    element.css('text-shadow', color+' 0px 0px 50px');
                    setTimeout(function() {
                        element.css('text-shadow', '#fff 0px 0px 50px');
                    }, 3000);
                }
                Mautic.ActiveVisitorsCount = response.viewingVisitors;
            }

            Mautic.moderatedIntervalCallbackIsComplete('ActiveVisitorsLoop');
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);

            Mautic.moderatedIntervalCallbackIsComplete('ActiveVisitorsLoop');
        }
    });
}

Mautic.updateWidgetForm = function (element) {
    Mautic.activateLabelLoadingIndicator('widget_type');
    var formWrapper = mQuery(element).closest('form');
    var WidgetFormValues = formWrapper.serializeArray();
    Mautic.ajaxActionRequest('dashboard:updateWidgetForm', WidgetFormValues, function(response) {
        if (response.formHtml) {
            var formHtml = mQuery(response.formHtml);
            formHtml.find('#widget_buttons').addClass('hide hidden');
            formWrapper.html(formHtml.children());
        }
        Mautic.removeLabelLoadingIndicator();
    });
};
