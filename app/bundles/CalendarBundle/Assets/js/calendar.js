//CalendarBundle
Mautic.calendarOnLoad = function (container) {
    jQuery('#calendar').fullCalendar({
        events: mauticAjaxUrl + "?action=calendar:generateData"
    });
};
