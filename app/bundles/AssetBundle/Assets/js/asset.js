//AssetBundle
Mautic.assetOnLoad = function (container) {
    if (mQuery(container + ' form[name="asset"]').length) {
       Mautic.activateCategoryLookup('asset', 'asset');
    }
    if (typeof Mautic.renderDownloadChartObject === 'undefined') {
	    Mautic.renderDownloadChart();
	}
};

Mautic.assetOnUnload = function(id) {
	if (id === '#app-content') {
		delete Mautic.renderDownloadChartObject;
	}
};

Mautic.getAssetId = function() {
	return mQuery('input#itemId').val();
} 

Mautic.renderDownloadChart = function (chartData) {
	if (!mQuery('#download-chart').length) {
		return;
	}
    if (!chartData) {
    	chartData = mQuery.parseJSON(mQuery('#download-chart-data').text());
    }
    var ctx = document.getElementById("download-chart").getContext("2d");
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

	if (typeof Mautic.renderDownloadChartObject === 'undefined') {
	    Mautic.renderDownloadChartObject = new Chart(ctx).Line(data, options);
    } else {
    	Mautic.renderDownloadChartObject.destroy();
    	Mautic.renderDownloadChartObject = new Chart(ctx).Line(data, options);
    }
};

Mautic.updateDownloadChart = function(element, amount, unit) {
	var element = mQuery(element);
	var wrapper = element.closest('ul');
	var button  = mQuery('#time-scopes .button-label');
	var assetId = Mautic.getAssetId();
	wrapper.find('a').removeClass('bg-primary');
	element.addClass('bg-primary');
	button.text(element.text());
	var query = "action=asset:updateDownloadChart&amount=" + amount + "&unit=" + unit + "&assetId=" + assetId;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (response.success) {
            	Mautic.renderDownloadChart(response.stats);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
}
