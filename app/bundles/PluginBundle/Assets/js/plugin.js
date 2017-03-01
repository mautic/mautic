/* PluginBundle */
Mautic.addNewPluginField = function (selector) {
    var items = mQuery( 'div.' + selector ).find( 'div.hide' );
    var currentItem = items.filter('.hide');
    var selectors;
    var nextItem = currentItem.first();

    if (nextItem.length) {
        currentItem = nextItem.removeClass('hide');
        selectors = currentItem.find('select');
        selectors.each(function () {
            mQuery(this).prop('disabled', false).trigger("chosen:updated");
        });
        currentItem.find('label').removeClass('disabled');
        currentItem.find('input[type="radio"]').prop('disabled', false).next().prop('disabled', false);
    }
    Mautic.stopIconSpinPostEvent();
};
Mautic.removePluginField = function (selector, indexClass) {
    var deleteCurrentItem = mQuery('#' + indexClass);

    selectors = deleteCurrentItem.find('select');
    selectors.each(function( ) {
        mQuery( this ).prop('disabled', true).trigger("chosen:updated");
    });
    deleteCurrentItem.find('input[type="radio"]').prop('disabled', true).next().prop('disabled', true);
    deleteCurrentItem.find('label').addClass('disabled');
    deleteCurrentItem.addClass('hide');

    Mautic.stopIconSpinPostEvent();
};

Mautic.initiateIntegrationAuthorization = function() {
    mQuery('#integration_details_in_auth').val(1);

    Mautic.postForm(mQuery('form[name="integration_details"]'), 'loadIntegrationAuthWindow');
};

Mautic.loadIntegrationAuthWindow = function(response) {
    if (response.newContent) {
        Mautic.processModalContent(response, '#IntegrationEditModal');
    } else {
        Mautic.stopPageLoadingBar();
        Mautic.stopIconSpinPostEvent();
        mQuery('#integration_details_in_auth').val(0);

        if (response.authUrl) {
            var generator = window.open(response.authUrl, 'integrationauth', 'height=500,width=500');

            if (!generator || generator.closed || typeof generator.closed == 'undefined') {
                alert(mauticLang.popupBlockerMessage);
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
        var integration = '.integration-' + response.name;
        if (response.enabled) {
            mQuery(integration).removeClass('integration-disabled');
        } else {
            mQuery(integration).addClass('integration-disabled');
        }
    } else {
        Mautic.filterIntegrations();
    }
    mQuery('[data-toggle="tooltip"]').tooltip();
};

Mautic.filterIntegrations = function(update) {
    var filter = mQuery('#integrationFilter').val();

    if (update) {
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "POST",
            data: "action=plugin:setIntegrationFilter&plugin=" + filter
        });
    }

    //activate shuffles
    if (mQuery('.shuffle-integrations').length) {
        var grid = mQuery(".shuffle-integrations");

        //give a slight delay in order for images to load so that shuffle starts out with correct dimensions
        setTimeout(function () {
            grid.shuffle('shuffle', function($el, shuffle) {
                if (filter) {
                    return $el.hasClass('plugin' + filter);
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

Mautic.getIntegrationLeadFields = function (integration, el, settings) {
    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));

    if (typeof settings == 'undefined') {
        settings = {};
    }

    var data = {integration: integration, settings: settings};

    mQuery('#leadFieldsContainer').html('');

    Mautic.ajaxActionRequest('plugin:getIntegrationLeadFields', data,
        function(response) {
            if (response.success) {
                mQuery('#leadFieldsContainer').replaceWith(response.html);
                Mautic.onPageLoad('#leadFieldsContainer');

                if (mQuery('#fields-tab').length) {
                    mQuery('#fields-tab').removeClass('hide');
                }
            } else {
                if (mQuery('#fields-tab').length) {
                    mQuery('#fields-tab').addClass('hide');
                }
            }
            Mautic.removeLabelLoadingIndicator();
        }
    );
};

Mautic.getIntegrationCompanyFields = function (integration, el, settings) {
    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));

    if (typeof settings == 'undefined') {
        settings = {};
    }

    var data = {integration: integration, settings: settings};

    mQuery('#companyFieldsContainer').html('');

    Mautic.ajaxActionRequest('plugin:getIntegrationCompanyFields', data,
        function(response) {
            if (response.success) {
                mQuery('#companyFieldsContainer').replaceWith(response.html);
                Mautic.onPageLoad('#companyFieldsContainer');

                if (mQuery('#company-fields-container').length) {
                    mQuery('#company-fields-container').removeClass('hide');
                }
            } else {
                if (mQuery('#company-fields-container').length) {
                    mQuery('#company-fields-container').addClass('hide');
                }
            }
            Mautic.removeLabelLoadingIndicator();
        }
    );
};


Mautic.getIntegrationConfig = function (el, settings) {
    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));

    if (typeof settings == 'undefined') {
        settings = {};
    }

    settings.name = mQuery(el).attr('name');
    var data = {integration: mQuery(el).val(), settings: settings};

    mQuery('.integration-config-container').html('');

    Mautic.ajaxActionRequest('plugin:getIntegrationConfig', data,
        function (response) {
            if (response.success) {
                mQuery('.integration-config-container').html(response.html);
                Mautic.onPageLoad('.integration-config-container');
            }

            Mautic.removeLabelLoadingIndicator();
        }
    );
};