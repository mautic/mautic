Marketplace = {
    startInstall: function(elHtml, package) {
        mQuery(elHtml).attr('disable', true);
        var last_response_len = false;
        var progressBar = mQuery('#composer-progress .progress-bar');
        var interval = Marketplace.startProgressBar(progressBar);

        mQuery.ajax({
            type: 'GET',
            url: mauticBaseUrl+'s/marketplace/install/'+package+'/step/composer',
            xhrFields: {
                onprogress: function(e)
                {
                    var this_response, response = e.currentTarget.response;
                    if (last_response_len === false) {
                        this_response = response;
                        last_response_len = response.length;
                    } else {
                        this_response = response.substring(last_response_len);
                        last_response_len = response.length;
                    }
                    Marketplace.logProgress(this_response);
                }
            },
            complete: function() {
                clearInterval(interval);
                progressBar.css('width', '100%');
                progressBar.attr('aria-valuenow', 100);
            },
        });
    },

    logProgress: function(message) {
        var progressWrapper = mQuery('#log-wrapper');
        var logWrapper;
        if ( progressWrapper.children().length === 0 ) {
            logWrapper = mQuery('<pre/>');
            progressWrapper.append(logWrapper);
        } else {
            logWrapper = progressWrapper.find('pre');
            logWrapper.append("\n");
        }
        logWrapper.append(message);
    },

    startProgressBar: function(progressBar) {
        var expectedRuntime = progressBar.attr('data-expected-runtime');
        var currentRuntime = 0;
        var interval = setInterval(function() {
            width = currentRuntime / expectedRuntime * 100;
            progressBar.css('width', width + '%');
            progressBar.attr('aria-valuenow', width);
            if (width >= 100) {
                clearInterval(interval);
            }
            currentRuntime += 1;
        }, 1000);

        return interval;
    }
};
