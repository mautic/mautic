//CalendarBundle
Mautic.calendarOnLoad = function (container) {
    MauticVars.showLoadingBar = false;
    mQuery('#calendar').fullCalendar({
        events: mauticAjaxUrl + "?action=calendar:generateData",
        eventRender: function(event, element) {
            if (event.iconClass) {
                element.find('.fc-title').before(mQuery('<i />').addClass(event.iconClass));
            }

        	if (event.attr) {
        		element.attr(event.attr);
        	}
        	if (event.description) {
        		element.tooltip({'title': event.description});
        	}
	    }
    });
};
