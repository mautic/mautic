//DashboardBundle
Mautic.dashboardOnLoad = function (container) {
    Mautic.initWidgetSorting();
    Mautic.initDateRangePicker();
    Mautic.initWidgetRemoveButtons(mQuery('#dashboard-widgets'));
};

Mautic.dashboardOnUnload = function(id) {
    // Trash initialized dashboard vars on app content change.
    mQuery('.jvectormap-tip').remove();
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
    Mautic.initWidgetRemoveButtons(widgetHtml);
    Mautic.saveWidgetSorting();
}

Mautic.initWidgetSorting = function () {
    var widgetsWrapper = mQuery('#dashboard-widgets');
    widgetsWrapper.sortable({
        handle: '.card-header h4',
        placeholder: 'sortable-placeholder',
        items: '.widget',
        opacity: 0.9,
        scroll: false,
        scrollSensitivity: 5,
        scrollSpeed: 5,
        tolerance: "pointer",
        cursor: 'move',
        cursorAt: { left: 0, top: 0 },
        forcePlaceholderSize: true,
        appendTo: 'body',
        helper: 'clone',
        stop: function() {
            Mautic.saveWidgetSorting();
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

Mautic.initWidgetRemoveButtons = function (scope) {
    scope.find('.remove-widget').on('click', function(e) {
        e.preventDefault();
        var button = mQuery(this);
        var wrapper = button.closest('.widget');
        var widgetId = wrapper.attr('data-widget-id');
        wrapper.hide('slow');
        Mautic.ajaxActionRequest('dashboard:delete', {widget: widgetId}, function(response) {
            if (!response.success) {
                wrapper.show('slow');
            }
        });
    });
    
};
