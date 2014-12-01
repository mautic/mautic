/* AddonBundle */

Mautic.loadIntegrationAuthWindow = function(url, keyType, network, popupMsg) {
    //get the key needed if required
    var base = '#integration_details_apiKeys_';

    if (keyType) {
        var apiKey = mQuery(base+keyType).val();

        //replace the placeholder
        url = url.replace('{'+keyType+'}', apiKey);
    }

    var generator = window.open(url, 'socialauth','height=400,width=500');

    if(!generator || generator.closed || typeof generator.closed=='undefined') {
        alert(popupMsg);
    }
};

/**
 * @param cookieSet
 * @param network
 * @param token
 * @param code
 * @param callbackUrl
 */
Mautic.handleIntegrationCallback = function (cookieSet, network, token, code, callbackUrl) {
    //get the keys
    var base = '#integration_details_apiKeys_';

    var clientId = window.opener.mQuery(base + 'clientId').val();
    var clientSecret = window.opener.mQuery(base + 'clientSecret').val();

    if (!cookieSet) {
        mQuery.cookie('mautic_integration_clientid', clientId, {path: '/'});
        mQuery.cookie('mautic_integration_clientsecret', clientSecret, {path: '/'});
        //reload to make the cookie available
        window.location.href = window.location.href + "&cookiesSet=1";
    }

    //perform callback
    var query =
        '&code=' + code +
        '&state=' + token +
        '&' + network + '_csrf_token=' + token;

    mQuery.ajax({
        url: callbackUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            window.location = response.url;
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
};