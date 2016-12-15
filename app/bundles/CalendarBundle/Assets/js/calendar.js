//CalendarBundle
Mautic.calendarOnLoad = function (container) {
    Mautic.loadCalendarEvents(container);
};

Mautic.calendarModalOnLoad = function (container, response) {
    mQuery('#calendar').fullCalendar( 'refetchEvents' );
    mQuery(container + " a[data-toggle='ajax']").off('click.ajax');
    mQuery(container + " a[data-toggle='ajax']").on('click.ajax', function (event) {
        event.preventDefault();
        mQuery('.modal').modal('hide');
        return Mautic.ajaxifyLink(this, event);
    });
};

Mautic.initializeCalendarModals = function (container) {
    mQuery(container + " *[data-toggle='ajaxmodal']").off('click.ajaxmodal');
    mQuery(container + " *[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
        event.preventDefault();
        Mautic.ajaxifyModal(this, event);
    });
}

Mautic.loadCalendarEvents = function (container) {
    mQuery('#calendar').fullCalendar({
        events: mauticAjaxUrl + "?action=calendar:generateData",
        lang: 'en',
        eventLimit: true,
        eventLimitText: "more",
        eventRender: function(event, element) {
            element = mQuery(element);
            if (event.iconClass) {
                element.find('.fc-title').before(mQuery('<i />').addClass(event.iconClass));
            }
            if (event.attr) {
                element.attr(event.attr);
            }
            if (event.description) {
                var checkDay = new Date(event.start._d);
                if (checkDay.getDay() == 0) {
                    element.tooltip({'title': event.description, placement: 'right'});
                } else {
                    element.tooltip({'title': event.description, placement: 'left'});
                }
            }
        },
        loading: function(bool) {
            // if calendar events are loaded
            if (!bool) {
                //initialize ajax'd modals
                Mautic.initializeCalendarModals(container);
            }
        },
        eventDrop: function(event, delta, revertFunc) {
            mQuery.ajax({
                url: mauticAjaxUrl + "?action=calendar:updateEvent",
                data: 'entityId=' + event.entityId + '&entityType=' + event.entityType + '&setter=' + event.setter + '&startDate=' + event.start.format(),
                type: "POST",
                dataType: "json",
                success: function (response) {
                    if (!response.success) {
                        revertFunc();
                    }
                    Mautic.initializeCalendarModals(container);
                    if (response.flashes) {
                        Mautic.setFlashes(response.flashes);
                        Mautic.hideFlashes();
                    }
                },
                error: function (response, textStatus, errorThrown) {
                    revertFunc();
                    Mautic.processAjaxError(response, textStatus, errorThrown, true);
                }
            });
        }
    });
}
