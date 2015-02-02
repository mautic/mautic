//AssetBundle
Mautic.assetOnLoad = function (container) {
    if (typeof Mautic.renderDownloadChartObject === 'undefined') {
	    Mautic.renderDownloadChart();
	}

    mQuery("#asset_file").change(function() {
        Mautic.previewBeforeUpload(this);
    });
};

Mautic.assetOnUnload = function(id) {
	if (id === '#app-content') {
		delete Mautic.renderDownloadChartObject;
	}
};

Mautic.getAssetId = function() {
	return mQuery('input#itemId').val();
};

Mautic.renderDownloadChart = function (chartData) {
	if (!mQuery('#download-chart').length) {
		return;
	}
    if (!chartData) {
    	chartData = mQuery.parseJSON(mQuery('#download-chart-data').text());
    }
    var ctx = document.getElementById("download-chart").getContext("2d");
    var options = {};

	if (typeof Mautic.renderDownloadChartObject === 'undefined') {
	    Mautic.renderDownloadChartObject = new Chart(ctx).Line(chartData, options);
    } else {
    	Mautic.renderDownloadChartObject.destroy();
    	Mautic.renderDownloadChartObject = new Chart(ctx).Line(chartData, options);
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
};

Mautic.previewBeforeUpload = function(input) {
    if (input.files && input.files[0]) {
        var filename = input.files[0].name.toLowerCase();
        var extension = filename.substr((filename.lastIndexOf('.') +1));
        var reader = new FileReader();
        var element = mQuery('<i />').addClass('fa fa-upload fa-5x');

        if (mQuery.inArray(extension, ['jpg', 'jpeg', 'gif', 'png']) !== -1) {
            reader.onload = function (e) {
                element = mQuery('<img />').addClass('img-thumbnail').attr('src', e.target.result);
                mQuery('.thumbnail-preview').empty().append(element);
            }
        } else if (extension === 'pdf') {
            reader.onload = function (e) {
                element = mQuery('<iframe />').attr('src', e.target.result);
                mQuery('.thumbnail-preview').empty().append(element);
            }
        }

        mQuery('.thumbnail-preview').empty().append(element);
        reader.readAsDataURL(input.files[0]);
    }
};

Mautic.updateRemoteBrowser = function(provider, path) {
    path = typeof path !== 'undefined' ? path : '';

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: "action=asset:fetchRemoteFiles&provider=" + provider + "&path=" + path,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery('div#remoteFileBrowser').html(response.output);
            } else {
                // TODO - Add error handler
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    })
};

Mautic.selectRemoteFile = function(url) {
    mQuery('#asset_remotePath').val(url);
    mQuery('#RemoteFileModal').modal('hide');
};

Mautic.changeAssetStorageLocation = function() {
    if (mQuery('#asset_storageLocation_0').prop('checked')) {
        mQuery('#storage-local').removeClass('hide');
        mQuery('#storage-remote').addClass('hide');
    } else {
        mQuery('#storage-local').addClass('hide');
        mQuery('#storage-remote').removeClass('hide');
    }
};
