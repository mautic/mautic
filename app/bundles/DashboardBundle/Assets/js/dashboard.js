//DashboardBundle
Mautic.dashboardOnLoad = function (container) {
    Mautic.initWidgetSorting();
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
    var bodyOverflow = {};

    widgetsWrapper.sortable({
        handle: '.card-header h4',
        placeholder: 'sortable-placeholder',
        items: '.widget',
        opacity: 0.9,
        scroll: true,
        scrollSpeed: 10,
        tolerance: "pointer",
        cursor: 'move',
        appendTo: '#dashboard-widgets',

        helper: function(e, ui) {
            // Ensure the draggable retains it's original size and that the margin doesn't cause things to bounce around
            ui.children().each(function() {
                mQuery(this).width(mQuery(this).width());
                mQuery(this).height(mQuery(this).height());
            });

            // Fix body overflow that messes sortable up
            bodyOverflow.overflowX = mQuery('body').css('overflow-x');
            bodyOverflow.overflowY = mQuery('body').css('overflow-y');
            mQuery('body').css({
                overflowX: 'visible',
                overflowY: 'visible'
            });

            mQuery("#dashboard-widgets .widget").each(function(i) {
                var item = mQuery(this);
                var item_clone = item.clone();

                var canvas = item.find('canvas').first();
                if (canvas.length) {
                    // Copy the canvas
                    var destCanvas = item_clone.find('canvas').first();
                    var destCtx = destCanvas[0].getContext('2d');
                    destCtx.drawImage(canvas[0], 0, 0);
                }

                item.data("clone", item_clone);
                var position = item.position();
                item_clone
                    .css({
                        left: position.left,
                        top: position.top,
                        width: item.width(),
                        visibility: "visible",
                        position: "absolute",
                        zIndex: 1
                    });

                item.css('visibility', 'hidden');
                mQuery("#cloned-widgets").append(item_clone);
            });

            return ui;
        },
        start: function(e, ui) {
            ui.helper.css('visibility', 'visible');
            ui.helper.data("clone").hide();
        },
        sort: function(e, ui) {
            var card = ui.item.find('.card').first();
            // Keep the placeholder width and height of the same as that of the inner card's width to prevent the jump effect
            ui.placeholder.width(card.width());
            ui.placeholder.height(card.height());
            // Prevent margin from pushing the elements out of the way
            ui.placeholder.css({
                marginTop: "5px",
                marginBottom: "5px",
                marginLeft: 0,
                marginRight: 0
            });
        },
        stop: function() {
            // Restore original overflow
            mQuery('body').css(bodyOverflow);

            mQuery("#dashboard-widgets .widget.exclude-me").each(function() {
                var item = mQuery(this);
                var clone = item.data("clone");
                var position = item.position();

                clone.css("left", position.left);
                clone.css("top", position.top);
                clone.show();
                item.removeClass("exclude-me");
            });

            mQuery("#dashboard-widgets .widget").css("visibility", "visible");
            mQuery("#cloned-widgets .widget").remove();

            Mautic.saveWidgetSorting();
        },
        change: function(e, ui) {
            mQuery("#dashboard-widgets .widget:not(.exclude-me)").each(function() {
                var item = mQuery(this);
                var clone = item.data("clone");
                clone.stop(true, false);
                var position = item.position();
                clone.animate({
                    left: position.left,
                    top: position.top
                }, 200);
            });
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
            Mautic.onPageLoad('#widget_params');
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

Mautic.exportDashboardLayout = function(text, baseUrl) {
    var name = prompt(text, "");

    if (name !== null) {
        if (name) {
            baseUrl = baseUrl + "?name=" + encodeURIComponent(name);
        }

        window.location = baseUrl;
    }
};

Mautic.saveDashboardLayout = function(text) {
    var name = prompt(text, "");

    if (name) {
        mQuery.ajax({
            type: 'POST',
            url: mauticBaseUrl+'s/dashboard/save',
            data: {name: name}
        });
    }
};
