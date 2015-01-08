/* AddonBundle */

Mautic.initiateIntegrationAuthorization = function() {
    mQuery('#integration_details_in_auth').val(1);

    Mautic.postForm(mQuery('form[name="integration_details"]'), 'loadIntegrationAuthWindow');
};

Mautic.loadIntegrationAuthWindow = function(response) {
    Mautic.stopPageLoadingBar();
    Mautic.stopIconSpinPostEvent();
    mQuery('#integration_details_in_auth').val(0);

    if (response.authUrl) {
        var generator = window.open(response.authUrl, 'integrationauth','height=500,width=500');

        if(!generator || generator.closed || typeof generator.closed=='undefined') {
            alert(response.popupBlockerMessage);
        }
    }
};

Mautic.refreshIntegrationForm = function() {
    var opener = window.opener;
    if(opener) {
            var form = opener.mQuery('form[name="integration_details"]');
            if (form.length) {
                var action = form.attr('action');
                if (action) {
                    opener.Mautic.startModalLoadingBar('#IntegrationEditModal');
                    opener.Mautic.loadAjaxModal('#IntegrationEditModal', action);
                }
            }
    }

    window.close()
};