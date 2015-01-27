/* AddonBundle */

Mautic.initiateIntegrationAuthorization = function() {
    mQuery('#integration_details_in_auth').val(1);

    Mautic.postForm(mQuery('form[name="integration_details"]'), 'loadIntegrationAuthWindow');
};

Mautic.loadIntegrationAuthWindow = function(response) {
    if (response.newContent) {
        response.target = '#IntegrationEditModal .modal-body-content';
        Mautic.processPageContent(response);
    } else {
        Mautic.stopPageLoadingBar();
        Mautic.stopIconSpinPostEvent();
        mQuery('#integration_details_in_auth').val(0);

        if (response.authUrl) {
            var generator = window.open(response.authUrl, 'integrationauth', 'height=500,width=500');

            if (!generator || generator.closed || typeof generator.closed == 'undefined') {
                alert(response.popupBlockerMessage);
            }
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

Mautic.integrationOnLoad = function(container, response) {
    if (response && response.name) {
        var integration = '.integration-' + response.name + ' .fa-check';
        if (response.enabled) {
            mQuery(integration).removeClass('hide');
        } else {
            mQuery(integration).addClass('hide');
        }
    } else {
        Mautic.filterIntegrations();
    }
};

Mautic.filterIntegrations = function(update) {
    var filter = mQuery('#integrationFilter').val();

    if (update) {
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "POST",
            data: "action=addon:setIntegrationFilter&addon=" + filter
        });
    }

    //activate shuffles
    if (mQuery('.shuffle-integrations').length) {
        var grid = mQuery(".shuffle-integrations");

        //give a slight delay in order for images to load so that shuffle starts out with correct dimensions
        setTimeout(function () {
            grid.shuffle('shuffle', function($el, shuffle) {
                if (filter) {
                    return $el.hasClass('addon' + filter);
                } else {
                    return true;
                }
            });

            // Update shuffle on sidebar minimize/maximize
            mQuery("html")
                .on("fa.sidebar.minimize", function () {
                    grid.shuffle("update");
                })
                .on("fa.sidebar.maximize", function () {
                    grid.shuffle("update");
                });
        }, 500);
    }
};