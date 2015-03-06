//AssetBundle
Mautic.assetOnLoad = function (container) {
    if (typeof Mautic.renderDownloadChartObject === 'undefined') {
	    Mautic.renderDownloadChart();
	}

    // todo fix this
    mQuery("#asset_file").change(function() {
        Mautic.previewBeforeUpload(this);
    });

    if (typeof mauticAssetUploadEndpoint !== 'undefined' && mQuery('div#dropzone').length)
    {
        Mautic.initializeDropzone();
    }
};

Mautic.assetOnUnload = function(id) {
	if (id === '#app-content') {
		delete Mautic.renderDownloadChartObject;
        delete Mautic.assetDropzone;
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

    var spinner = mQuery('<i class="fa fa-fw fa-spinner fa-spin"></i>');
    spinner.appendTo('#tab' + provider + ' a');

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: "action=asset:fetchRemoteFiles&provider=" + provider + "&path=" + path,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery('div#remoteFileBrowser').html(response.output);

                mQuery('.remote-file-search').quicksearch('#remoteFileBrowser .remote-file-list a');
            } else {
                // TODO - Add error handler
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function() {
            spinner.remove();
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
        mQuery('#remote-button').addClass('hide');
    } else {
        mQuery('#storage-local').addClass('hide');
        mQuery('#storage-remote').removeClass('hide');
        mQuery('#remote-button').removeClass('hide');
    }
};

Mautic.initializeDropzone = function() {
    var options = {
        url: mauticAssetUploadEndpoint,
        uploadMultiple: false,
        // dictDefaultMessage: 'test',
        init: function() {
            this.on("addedfile", function() {
                if (this.files[1] != null) {
                    this.removeFile(this.files[0]);
                }
            });
        }
    };

    Mautic.assetDropzone = new Dropzone("div#dropzone", options);

    Mautic.assetDropzone.on("sending", function(file, request, formData) {
        formData.append('tempId', mQuery('#asset_tempId').val());
    }).on("success", function(file, response, progress) {
        if (response.tmpFileName) {
            mQuery('#asset_tempName').val(response.tmpFileName);
        }

        var messageArea = mQuery('.mdropzone-error');
        if (response.error || !response.tmpFileName) {
            if (!response.error) {
                var errorText = '';
            } else {
                var errorText = (typeof response.error == 'object') ? response.error.text : response.error;
            }

            messageArea.text(errorText);
            messageArea.closest('.form-group').addClass('has-error').removeClass('is-success');

            // invoke the error
            var node, _i, _len, _ref, _results;
            file.previewElement.classList.add('dz-error');
            _ref = file.previewElement.querySelectorAll('data-dz-errormessage');
            _results = [];
            for (_i = 0, _len = _ref.length; _i < _len; _i++) {
              node = _ref[_i];
              _results.push(node.textContent = errorText);
            }
            return _results;
        } else {
            messageArea.text('');
            messageArea.closest('.form-group').removeClass('has-error').addClass('is-success');
        }

        var titleInput = mQuery('#asset_title');
        if (file.name && !titleInput.val()) {
            titleInput.val(file.name);
        }

        if (file.name) {
            mQuery('#asset_originalFileName').val(file.name);
        }
    }).on("error", function (file, response) {
        var messageArea = mQuery('.mdropzone-error');
        if (response.error) {
            if (!response.error) {
                var errorText = '';
            } else {
                var errorText = (typeof response.error == 'object') ? response.error.text : response.error;
            }

            messageArea.text(errorText);
            messageArea.closest('.form-group').addClass('has-error').removeClass('is-success');

            // invoke the error
            var node, _i, _len, _ref, _results;
            file.previewElement.classList.add('dz-error');
            _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]');
            _results = [];
            for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                node = _ref[_i];
                _results.push(node.textContent = errorText);
            }
            return _results;
        }
    });
}
