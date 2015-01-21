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

	Mautic.activateChosenSelect(mQuery('#'+elId+'_'+index+'_column'));
	mQuery("#"+elId+" *[data-toggle='tooltip']").tooltip({html: true, container: 'body'});
};

Mautic.removeReportRow = function(container) {
	mQuery("#"+container+" *[data-toggle='tooltip']").tooltip('destroy');
	mQuery('#' + container).remove();
};

Mautic.updateReportColumnList = function (source) {
	Mautic.activateLabelLoadingIndicator('report_source');
	mQuery.ajax({
	    url : mauticAjaxUrl,
	    type: 'post',
		data: "action=report:getColumnList&source=" + source,
	    success: function(response) {
	    	if (response.columns) {
				mQuery('#report_columns').html(response.columns);
				mQuery('#report_columns').multiSelect('refresh');

				// Remove any filters, they're no longer valid with different column lists
				mQuery('#report_filters').find('div').remove().end();

				var prototype = mQuery('#report_filters').data('prototype');
				prototype = prototype.replace(/([\s|\S]*?)class="form-control filter-columns">([\s|\S]*?)<\/select>([\s|\S]*?)/m, '$1class="form-control filter-columns">'+response.columns+"<\select>$3");
				mQuery('#report_filters').data('prototype', prototype);
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
            		Mautic.reportGraphs[id] = Mautic.renderReportLineGraph(mGraph.get(0).getContext("2d"), response.graph.line[0]);
            	}
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
}

Mautic.renderReportLineGraph = function (canvas, chartData) {
    var options = {};
    return new Chart(canvas).Line(chartData, options);
};

Mautic.renderReportPieGraph = function (canvas, chartData) {
    var options = {
        responsive: false,
        tooltipFontSize: 10,
        tooltipTemplate: "<%if (label){%><%}%><%= value %>x <%=label%>"};
    Mautic.pageTimePie = new Chart(canvas).Pie(chartData, options);
}

Mautic.getReportId = function() {
	return mQuery('#reportId').val();
}
