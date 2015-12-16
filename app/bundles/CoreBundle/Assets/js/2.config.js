Mautic.downloadIpLookupDataStore = function() {
    var ipService = mQuery('#config_coreconfig_ip_lookup_service').val();
    var ipAuth = mQuery('#config_coreconfig_ip_lookup_auth').val();

    mQuery('#iplookup_fetch_button_container .fa-spinner').removeClass('hide');

    Mautic.ajaxActionRequest('downloadIpLookupDataStore', {
        service: ipService,
        auth: ipAuth
    }, function (response) {
        mQuery('#iplookup_fetch_button_container .fa-spinner').addClass('hide');

        if (response.message) {
            mQuery('#iplookup_fetch_button_container').parent().removeClass('has-error').addClass('has-success');
            mQuery('#iplookup_fetch_button_container').next('.help-block').html(response.message);
        } else if (response.error) {
            mQuery('#iplookup_fetch_button_container').parent().removeClass('has-success').addClass('has-error');
            mQuery('#iplookup_fetch_button_container').next('.help-block').html(response.error);
        }
    });
};

Mautic.getIpLookupFormConfig = function() {
    var ipService = mQuery('#config_coreconfig_ip_lookup_service').val();

    Mautic.activateLabelLoadingIndicator('config_coreconfig_ip_lookup_service');

    Mautic.ajaxActionRequest('getIpLookupForm', {
        service: ipService
    }, function (response) {
        Mautic.removeLabelLoadingIndicator();

        mQuery('#ip_lookup_config_container').html(response.html);
        mQuery('#ip_lookup_attribution').html(response.attribution);
    });
};