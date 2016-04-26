//ReportBundle
Mautic.reportOnLoad = function (container) {
	// Activate search if the container exists
	if (mQuery(container + ' #list-search').length) {
		Mautic.activateSearchAutocomplete('list-search', 'report');
	}

	// Append an index of the number of filters on the edit form
	if (mQuery('div[id=report_filters]').length) {
		mQuery('div[id=report_filters]').data('index', mQuery('#report_filters > div').length);

		mQuery('div[id=report_tableOrder]').data('index', mQuery('#report_tableOrder > div').length);

		if (mQuery('.filter-columns').length) {
			mQuery('.filter-columns').each(function () {
				Mautic.updateReportFilterValueInput(this, true);
			});
		}
	}

	Mautic.initReportGraphs();
};

Mautic.reportOnUnload = function(id) {
	if (id === '#app-content') {
		delete Mautic.reportGraphs;
	}
};

/**
 * Written with inspiration from http://symfony.com/doc/current/cookbook/form/form_collections.html#allowing-new-tags-with-the-prototype
 */
Mautic.addReportRow = function(elId) {
	// Container with the prototype markup
	var prototypeHolder = mQuery('div[id="'+elId+'"]');

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

	var newColumnId = '#' + elId + '_' + index + '_column';

	// Update the column options if applicable
	if (typeof Mautic.reportPrototypeColumnOptions != 'undefined') {
		mQuery(newColumnId).html(Mautic.reportPrototypeColumnOptions);
	}

	if (elId == 'report_filters') {
		mQuery(newColumnId).on('change', function() {
			Mautic.updateReportFilterValueInput(this);
		});
		Mautic.updateReportFilterValueInput(newColumnId);
	}

	Mautic.activateChosenSelect(mQuery('#'+elId+'_'+index+'_column'));
	mQuery("#"+elId+" *[data-toggle='tooltip']").tooltip({html: true, container: 'body'});

};

Mautic.updateReportFilterValueInput = function (filterColumn, setup) {
	var types      = (typeof Mautic.reportPrototypeColumnTypes != 'undefined') ? Mautic.reportPrototypeColumnTypes : mQuery('#report_filters').data('column-types');
	var newValue   = mQuery(filterColumn).val();
	var filterId   = mQuery(filterColumn).attr('id');
	var filterType = types[newValue];

	// Get the value element
	var valueEl = mQuery(filterColumn).parent().parent().find('.filter-value');
	var valueVal = valueEl.val();

	var idParts = filterId.split("_");

	var valueId   = 'report_filters_' + idParts[2] + '_value';
	var valueName = 'report[filters][' + idParts[2] + '][value]';

	if (filterType == 'bool') {
		if (mQuery(valueEl).attr('type') != 'radio') {
			var template = mQuery('#filterValueYesNoTemplate .btn-group').clone(true);
			mQuery(template).find('input[type="radio"]').each(function () {
				mQuery(this).attr('name', valueName);
				var radioVal = mQuery(this).val();
				mQuery(this).attr('id', valueId + '_' + radioVal);
			});
			mQuery(valueEl).replaceWith(template);
		}

		if (setup) {
			mQuery('#' + valueId + '_' + valueVal).click();
		}
	} else if (mQuery(valueEl).attr('type') != 'text') {
		var newValueEl = mQuery('<input type="text" />').attr({
			id: valueId,
			name: valueName,
			'class': "form-control filter-value"
		});

		var replaceMe = (mQuery(valueEl).attr('type') == 'radio') ? mQuery(valueEl).parent().parent() : mQuery(valueEl);
		replaceMe.replaceWith(newValueEl);
	}

	// Activate datetime
	if (filterType == 'datetime' || filterType == 'date' || filterType == 'time') {
		Mautic.activateDateTimeInputs('#' + valueId, filterType);
	} else if (mQuery('#' + valueId).hasClass('calendar-activated')) {
		mQuery('#' + valueId).datetimepicker('destroy');
	}
};

Mautic.removeReportRow = function(container) {
	mQuery("#"+container+" *[data-toggle='tooltip']").tooltip('destroy');
	mQuery('#' + container).remove();
};

Mautic.updateReportSourceData = function (context) {
	Mautic.activateLabelLoadingIndicator('report_source');
	mQuery.ajax({
	    url : mauticAjaxUrl,
	    type: 'post',
		data: "action=report:getSourceData&context=" + context,
	    success: function(response) {
			mQuery('#report_columns').html(response.columns);
			mQuery('#report_columns').multiSelect('refresh');

			// Remove any filters, they're no longer valid with different column lists
			mQuery('#report_filters').find('div').remove().end();

			// Reset index
			mQuery('#report_filters').data('index', 0);

			// Update types
			Mautic.reportPrototypeColumnTypes = response.types;

			// Remove order
			mQuery('#report_tableOrder').find('div').remove().end();

			// Reset index
			mQuery('#report_tableOrder').data('index', 0);

			// Store options to update prototype
			Mautic.reportPrototypeColumnOptions = mQuery(response.columns);

			mQuery('#report_graphs').html(response.graphs);
			mQuery('#report_graphs').multiSelect('refresh');

			if (!response.graphs) {
				mQuery('#graphs-container').addClass('hide');
				mQuery('#graphs-tab').addClass('hide');
			} else {
				mQuery('#graphs-container').removeClass('hide');
				mQuery('#graphs-tab').removeClass('hide');
			}
		},
		error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
		},
		complete: function() {
			Mautic.removeLabelLoadingIndicator();
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

Mautic.initReportGraphs = function () {
	Mautic.reportGraphs = {};
	var graphs = mQuery('canvas.graph');
	mQuery.each(graphs, function(i, graph){
		var mGraph = mQuery(graph);
		if (mGraph.hasClass('graph-line')) {
			var id = mGraph.attr('id');
			if (typeof Mautic.reportGraphs[id] === 'undefined') {
				var graphData = mQuery.parseJSON(mQuery('#' + id + '-data').text());
				Mautic.reportGraphs[id] = Mautic.renderReportLineGraph(graph.getContext("2d"), graphData);
			}
		}
		if (mGraph.hasClass('graph-pie')) {
			var id = mGraph.attr('id');
			if (typeof Mautic.reportGraphs[id] === 'undefined') {
				var graphData = mQuery.parseJSON(mQuery('#' + id + '-data').text());
				Mautic.reportGraphs[id] = Mautic.renderReportPieGraph(graph.getContext("2d"), graphData);
			}
		}
	});
}

Mautic.updateReportGraph = function(element, amount, unit) {
	var canvas   = mQuery(element).closest('.panel').find('canvas');
	var id       = canvas.attr('id');
	var reportId = Mautic.getEntityId();
	var options  = {'graphName': id.replace(/\-/g, '.'), 'amount': amount, 'unit': unit};
	var query    = 'reportId=' + reportId + '&' + mQuery.param(options);

    var callback = function(response) {
        Mautic.reportGraphs[id].destroy();
        delete Mautic.reportGraphs[id];
        if (typeof response.graph.datasets != 'undefined') {
            Mautic.reportGraphs[id] = Mautic.renderReportLineGraph(canvas.get(0).getContext("2d"), response.graph);
        }
    };

    Mautic.getChartData(element, 'report:updateGraph', query, callback);
}

Mautic.renderReportLineGraph = function (canvas, chartData) {
    var options = {
        pointDotRadius : 2,
        datasetStrokeWidth : 1,
        bezierCurveTension : 0.2,
        multiTooltipTemplate: "<%= datasetLabel %>: <%= value %>"
    }
    return new Chart(canvas).Line(chartData, options);
};

Mautic.renderReportPieGraph = function (canvas, chartData) {
    var options = {
        responsive: false,
        tooltipFontSize: 10,
        tooltipTemplate: "<%if (label){%><%}%><%= value %>x <%=label%>",
	    segmentStrokeWidth : 1};
    Mautic.pageTimePie = new Chart(canvas).Pie(chartData, options);
}
