//CalendarBundle
Mautic.calendarOnLoad = function (container) {
    jQuery('#calendar').fullCalendar({
        events: mauticAjaxUrl + "?action=calendar:generateData",
        eventRender: function(event, element) {
        	if (event.attr) {
        		element.attr(event.attr);
        	}
        	if (event.description) {
        		element.tooltip({'title': event.description});
        	}
	    }
    });
};
