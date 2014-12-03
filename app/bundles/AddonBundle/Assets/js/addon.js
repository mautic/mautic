/* AddonBundle */

Mautic.initiateIntegrationAuthorization = function() {
    mQuery('#integration_details_in_auth').val(1);

    Mautic.postForm(mQuery('form[name="integration_details"]'), 'loadIntegrationAuthWindow');
};

Mautic.loadIntegrationAuthWindow = function(response) {
    Mautic.stopPageLoadingBar();
    Mautic.stopIconSpinPostEvent();
    if (response.authUrl) {
        var generator = window.open(response.authUrl, 'integraitonauth','height=400,width=500');

        if(!generator || generator.closed || typeof generator.closed=='undefined') {
            alert(response.popupBlockerMessage);
        }
    }
};