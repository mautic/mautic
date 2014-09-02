//ReportBundle
Mautic.reportOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'reportOnLoad');
    }
};

Mautic.preprocessSaveReportForm = function(form) {
	var selectedColumns = mQuery(form + ' #report_columns');

	mQuery(selectedColumns).find('option').each(function($this) {
		mQuery(this).attr('selected', 'selected');
	});
};

Mautic.moveReportColumns = function(fromSelect, toSelect) {
	mQuery('#' + fromSelect + ' option:selected').remove().appendTo('#' + toSelect);

	mQuery('#' + toSelect).find('option').each(function($this) {
		mQuery(this).prop('selected', false);
	});
};

Mautic.reorderColumns = function(select, direction) {
	var options = mQuery('#' + select + ' option:selected');

	if (options.length) {
		(direction == 'up') ? options.first().prev().before(options) : options.last().next().after(options);
	}
};
