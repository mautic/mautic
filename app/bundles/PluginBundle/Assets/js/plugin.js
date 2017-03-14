/* PluginBundle */
Mautic.matcheFields = function (index, object, integration) {
    var integrationField = mQuery('#integration_details_featureSettings_'+object+'Fields_i_' + index).val();
    var mauticField = mQuery('#integration_details_featureSettings_'+object+'Fields_m_' + index + ' option:selected').val();
    if (object == 'lead') {
        var updateMauticField = mQuery('input[name="integration_details[featureSettings]['+object+'Fields][update_mautic' + index + ']"]:checked').val();
    } else {
        var updateMauticField = mQuery('input[name="integration_details[featureSettings]['+object+'Fields][update_mautic_company' + index + ']"]:checked').val();
    }
    Mautic.ajaxActionRequest('plugin:matchFields', {object: object, integration: integration, integrationField : integrationField, mauticField: mauticField, updateMautic : updateMauticField}, function(response) {
        var theMessage = (response.success) ? '<i class="fa fa-check-circle text-success"></i>' : '';
        mQuery('#matched-' + index + "-" + object).html(theMessage);
    });
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

Mautic.integrationConfigOnLoad = function(container) {
    if (mQuery('.fields-container select.integration-field').length) {
        var selects = mQuery('.fields-container select.integration-field');
        selects.on('change', function() {
            var select   = mQuery(this),
                newValue = select.val(),
                previousValue = select.attr('data-value');
            select.attr('data-value', newValue);

            var groupSelects = mQuery(this).closest('.fields-container').find('select.integration-field').not(select);

            // Enable old value
            if (previousValue) {
                mQuery('option[value="' + previousValue + '"]', groupSelects).each(function() {
                    if (!mQuery(this).closest('select').prop('disabled')) {
                        mQuery(this).prop('disabled', false);
                        mQuery(this).removeAttr('disabled');
                    }
                });
            }

            if (newValue) {
                mQuery('option[value="' + newValue + '"]', groupSelects).each(function() {
                    if (!mQuery(this).closest('select').prop('disabled')) {
                        mQuery(this).prop('disabled', true);
                        mQuery(this).attr('disabled', 'disabled');
                    }
                });
            }

            groupSelects.each(function() {
                mQuery(this).trigger('chosen:updated');
            });
        });

        selects.each(function() {
            if (!mQuery(this).closest('.field-container').hasClass('hide')) {
                mQuery(this).trigger('change');
            }
        });
    }
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
                Mautic.integrationConfigOnLoad('#leadFieldsContainer');
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
                Mautic.integrationConfigOnLoad('#companyFieldsContainer');

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
                Mautic.onPageLoad('.integration-config-container', response);
            }

            Mautic.integrationConfigOnLoad('.integration-config-container');
            Mautic.removeLabelLoadingIndicator();
        }
    );
};