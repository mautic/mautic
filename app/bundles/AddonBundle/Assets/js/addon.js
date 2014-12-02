/* AddonBundle */


Mautic.loadIntegrationAuthWindow = function(url, keyType, network, popupMsg) {
    //get the key needed if required
    var base = '#integration_details_apiKeys_';

    if (keyType) {
        var apiKey = mQuery(base+keyType).val();

        //replace the placeholder
        url = url.replace('{'+keyType+'}', encodeURIComponent(apiKey));
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
Mautic.handleIntegrationCallback = function (network, token, code, callbackUrl, clientIdKey, clientSecretKey) {
    //get the keys
    var base = '#integration_details_apiKeys_';

    if (typeof clientIdKey == 'undefined') {
        clientIdKey = 'clientId';
    }

    if (typeof clientSecretKey == 'undefined') {
        clientSecretKey = 'clientSecret';
    }

    var clientId = window.opener.mQuery(base + clientIdKey).val();
    var clientSecret = window.opener.mQuery(base + clientSecretKey).val();

    mQuery.cookie('mautic_integration_clientid', clientId, {path: '/'});
    mQuery.cookie('mautic_integration_clientsecret', clientSecret, {path: '/'});

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