Marketplace = {
    startInstall: function(elHtml, package) {
        mQuery(elHtml).attr('disable', true);
        Marketplace.stepComposer(package, function() {
            Marketplace.stepDatabase(package, function() {});
        });
    },

    stepComposer: function(package, callback) {
        Marketplace.runStep('s/marketplace/install/'+package+'/step/composer', '#composer-progress .progress-bar', callback)
    },

    stepDatabase: function(package, callback) {
        Marketplace.runStep('s/marketplace/install/'+package+'/step/database', '#database-progress .progress-bar', callback)
    },

    runStep: function(url, progressBarSelector, callback) {
        var last_response_len = false;
        var progressBar = mQuery(progressBarSelector);
        var interval = Marketplace.startProgressBar(progressBar);

        mQuery.ajax({
            type: 'GET',
            url: mauticBaseUrl+url,
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
                callback();
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
