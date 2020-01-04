Marketplace = {
    startInstall: function(elHtml, package) {
        mQuery(elHtml).attr('disable', true);
        var last_response_len = false;
        mQuery.ajax({
            type: 'GET',
            url: mauticBaseUrl+'s/marketplace/install/'+package+'/step/composer',
            showLoadingBar: true,
            xhrFields: {
                onprogress: function(e)
                {
                    console.log('onprogress');
                    var this_response, response = e.currentTarget.response;
                    if(last_response_len === false)
                    {
                        this_response = response;
                        last_response_len = response.length;
                    }
                    else
                    {
                        this_response = response.substring(last_response_len);
                        last_response_len = response.length;
                    }
                    console.log(this_response);
                }
            },
            success: function() {
                console.log('success');
            }
        });
    }
};
