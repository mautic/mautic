//AssetBundle
Mautic.assetOnLoad = function (container) {
    if (mQuery(container + ' form[name="asset"]').length) {
       Mautic.activateCategoryLookup('asset', 'asset');
    }
    Mautic.renderDownloadChart();
};

Mautic.renderDownloadChart = function (container) {
	if (!mQuery('#download-chart').length) {
		return;
	}
    var ctx = document.getElementById("download-chart").getContext("2d");
    var initialData = mQuery.parseJSON(mQuery('#download-chart-data').text());
    var options = {};
    var data = {
	    labels: initialData.labels,
	    datasets: [
	        {
	            label: "My Second dataset",
	            fillColor: "rgba(151,187,205,0.2)",
	            strokeColor: "rgba(151,187,205,1)",
	            pointColor: "rgba(151,187,205,1)",
	            pointStrokeColor: "#fff",
	            pointHighlightFill: "#fff",
	            pointHighlightStroke: "rgba(151,187,205,1)",
	            data: initialData.values
	        }
	    ]
	};
    var downloadChart = new Chart(ctx).Line(data, options);
};
