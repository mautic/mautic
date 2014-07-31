/* SocialBundle */

Mautic.loadAuthModal = function(url, keyType, network, popupMsg) {
    //get the key needed if required
    var base = '#socialmedia_config_services_'+network+'_apiKeys_';

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
 *
 * @param network
 * @param token
 * @param code
 * @param callbackUrl
 */
Mautic.handleCallback = function (network, token, code, callbackUrl) {
    //get the keys
    var base = '#socialmedia_config_services_'+network+'_apiKeys_';

    var clientId     = window.opener.mQuery(base+'clientId').val();
    var clientSecret = window.opener.mQuery(base+'clientSecret').val();

    //perform callback
    var query = 'clientId=' + clientId +
                '&clientSecret=' + clientSecret +
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
            alert(errorThrown);
        }
    });

}