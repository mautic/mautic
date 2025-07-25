Mautic.downloadIpLookupDataStore = function() {
    var ipService = mQuery('#config_coreconfig_ip_lookup_service').val();
    var ipAuth = mQuery('#config_coreconfig_ip_lookup_auth').val();

    mQuery('#iplookup_fetch_button_container .ri-loader-3-line').removeClass('hide');

    Mautic.ajaxActionRequest('downloadIpLookupDataStore', {
        service: ipService,
        auth: ipAuth
    }, function (response) {
        mQuery('#iplookup_fetch_button_container .ri-loader-3-line').addClass('hide');

        if (response.message) {
            mQuery('#iplookup_fetch_button_container').parent().removeClass('has-error').addClass('has-success');
            mQuery('#iplookup_fetch_button_container').next('.help-block').html(response.message);
        } else if (response.error) {
            mQuery('#iplookup_fetch_button_container').parent().removeClass('has-success').addClass('has-error');
            mQuery('#iplookup_fetch_button_container').next('.help-block').html(response.error);
        }
    }, false, false, 'GET');
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
    }, false, false, "GET");
};

Mautic.configOnLoad = function(container) {
    /**
     * Manages accent color selection functionality.
     */
    if (mQuery('#config_themeconfig_accent').length) {
        document.querySelectorAll('input[type="radio"][data-attribute-toggle]').forEach(radio => {
            const hiddenInput = document.getElementById('config_themeconfig_accent');

            if (hiddenInput && hiddenInput.value) {
                const correspondingRadio = document.querySelector(
                    `input[name="accent"][data-attribute-value="${hiddenInput.value}"]`
                );
                if (correspondingRadio) correspondingRadio.checked = true;
            } else if (radio.checked) {
                if (hiddenInput) {
                    hiddenInput.value = radio.dataset.attributeValue;
                }
            }
        });

        // Handle radio button changes - update hidden input
        document.querySelectorAll('input[type="radio"][data-attribute-toggle]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const hiddenInput = document.getElementById('config_themeconfig_accent');
                    if (hiddenInput) {
                        hiddenInput.value = this.dataset.attributeValue;
                    }
                }
            });
        });
    }
};
