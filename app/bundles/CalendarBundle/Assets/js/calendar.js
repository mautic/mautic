//CalendarBundle
Mautic.calendarOnLoad = function (container) {
    Mautic.loadCalendarEvents(container);
};

Mautic.calendarModalOnLoad = function (container, response) {
    mQuery('#calendar').fullCalendar( 'refetchEvents' );
};

Mautic.loadCalendarEvents = function (container) {
    mQuery('#calendar').fullCalendar({
        events: mauticAjaxUrl + "?action=calendar:generateData",
        eventRender: function(event, element) {
            element = mQuery(element);
            if (event.iconClass) {
                element.find('.fc-title').before(mQuery('<i />').addClass(event.iconClass));
            }
            if (event.attr) {
                element.attr(event.attr);
            }
            if (event.description) {
                element.tooltip({'title': event.description});
            }
        },
        loading: function(bool) {
            // if calendar events are loaded
            if (!bool) {
                //initialize ajax'd modals
                mQuery(container + " *[data-toggle='ajaxmodal']").off('click.ajaxmodal');
                mQuery(container + " *[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
                    event.preventDefault();
                    Mautic.ajaxifyModal(this, event);
                });
            }
        }
    });
}
