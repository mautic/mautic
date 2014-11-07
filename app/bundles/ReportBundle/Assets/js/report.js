//ReportBundle
Mautic.reportOnLoad = function (container) {
	// Activate search if the container exists
	if (mQuery(container + ' #list-search').length) {
		Mautic.activateSearchAutocomplete('list-search', 'report');
	}

	// Append an index of the number of filters on the edit form
	if (mQuery('div[id=report_filters]').length) {
		mQuery('div[id=report_filters]').data('index', mQuery('#report_filters > div').length);
	}

	Mautic.initGraphs();
};

Mautic.reportOnUnload = function(id) {
	if (id === '#app-content') {
		delete Mautic.reportGraphs;
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
};

Mautic.updateColumnList = function () {
	var table = mQuery('select[id=report_source] option:selected').val();

	mQuery.ajax({
		type: "POST",
		url: mauticAjaxUrl + "?action=report:updateColumns",
		data: {table: table},
		dataType: "json",
		success: function (response) {
			// Remove the existing options in the column display section
			mQuery('#report_columns_available').find('option').remove().end();
			mQuery('#report_columns').find('option').remove().end();

			// Append the new options into the select list
			mQuery.each(response.columns, function(key, value) {
				mQuery('#report_columns_available')
					.append(mQuery('<option>')
						.attr('value', key)
						.text(value.label));
			});

			// Remove any filters, they're no longer valid with different column lists
			mQuery('#report_filters').find('div').remove().end();

			// TODO - Need to parse the prototype and replace the options in the column list for filters too
		},
		error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
		}
	});
};

Mautic.checkReportCondition = function(selector) {
	var option = mQuery('#' + selector + ' option:selected').val();
	var valueInput = selector.replace('condition', 'value');

	// Disable the value input if the condition is empty or notEmpty
	if (option == 'empty' || option == 'notEmpty') {
		mQuery('#' + valueInput).prop('disabled', true);
	} else {
		mQuery('#' + valueInput).prop('disabled', false);
	}
};

Mautic.initGraphs = function () {
	Mautic.reportGraphs = {};
	var graphs = mQuery('canvas.graph');
	mQuery.each(graphs, function(i, graph){
		var mGraph = mQuery(graph);
		if (mGraph.hasClass('graph-line')) {
			var id = mGraph.attr('id');
			if (typeof Mautic.reportGraphs[id] === 'undefined') {
				var graphData = mQuery.parseJSON(mQuery('#' + id + '-data').text());
				Mautic.reportGraphs[id] = Mautic.renderLineGraph(graph.getContext("2d"), graphData);
			}
		}
		if (mGraph.hasClass('graph-pie')) {
			var id = mGraph.attr('id');
			if (typeof Mautic.reportGraphs[id] === 'undefined') {
				var graphData = mQuery.parseJSON(mQuery('#' + id + '-data').text());
				Mautic.reportGraphs[id] = Mautic.renderPieGraph(graph.getContext("2d"), graphData);
			}
		}
	});
}

Mautic.updateReportGraph = function(element, options) {
	var id = options.graphName.replace(/\./g, '-');
	var element = mQuery(element);
	var wrapper = element.closest('ul');
	var button  = mQuery('#time-scopes .button-label');
	var reportId = Mautic.getReportId();
	wrapper.find('a').removeClass('bg-primary');
	element.addClass('bg-primary');
	button.text(element.text());
	var query = "action=report:updateGraph&reportId=" + reportId + '&' + mQuery.param(options);
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (response.success) {
            	Mautic.reportGraphs[id].destroy();
            	delete Mautic.reportGraphs[id];
            	var mGraph = mQuery('#' + id);
            	if (typeof response.graph.line != 'undefined') {
            		Mautic.reportGraphs[id] = Mautic.renderLineGraph(mGraph.get(0).getContext("2d"), response.graph.line[0]);
            	}
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
}

Mautic.renderLineGraph = function (canvas, chartData) {
    var options = {};
    var data = {
	    labels: chartData.labels,
	    datasets: [
	        {
	            fillColor: "rgba(151,187,205,0.2)",
	            strokeColor: "rgba(151,187,205,1)",
	            pointColor: "rgba(151,187,205,1)",
	            pointStrokeColor: "#fff",
	            pointHighlightFill: "#fff",
	            pointHighlightStroke: "rgba(151,187,205,1)",
	            data: chartData.values
	        }
	    ]
	};
    return new Chart(canvas).Line(data, options);
};

Mautic.renderPieGraph = function (canvas, chartData) {
    var options = {
        responsive: false,
        tooltipFontSize: 10,
        tooltipTemplate: "<%if (label){%><%}%><%= value %>x <%=label%>"};
    Mautic.pageTimePie = new Chart(canvas).Pie(chartData, options);
}

Mautic.getReportId = function() {
	return mQuery('#reportId').val();
}
