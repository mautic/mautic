//ReportBundle
Mautic.reportOnLoad = function (container) {
	// Activate search if the container exists
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'reportOnLoad');
    }

	// Append an index of the number of filters on the edit form
	if (mQuery('div[id=report_filters]').length) {
		mQuery('div[id=report_filters]').data('index', mQuery('#report_filters > div').length);
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

/**
 * Written with inspiration from http://symfony.com/doc/current/cookbook/form/form_collections.html#allowing-new-tags-with-the-prototype
 */
Mautic.addFilterRow = function() {
	// Container with the prototype markup
	var prototypeHolder = mQuery('div[id=report_filters]');

	// Fetch the index
	var index = prototypeHolder.data('index');

	// Fetch the prototype markup
	var prototype = prototypeHolder.data('prototype');

	// Replace the placeholder with our index
	var output = prototype.replace(/__name__/g, index);

	// Increase the index for the next row
	prototypeHolder.data('index', index + 1);

	// Render the new row
	prototypeHolder.append(output);
};

Mautic.removeFilterRow = function(container) {
	mQuery('#' + container).remove();
}
