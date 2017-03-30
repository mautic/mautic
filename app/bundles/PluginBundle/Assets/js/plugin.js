/* PluginBundle */
Mautic.addNewPluginField = function (selector) {
    var items = mQuery( 'div.' + selector ).find( 'div.hide' );
    var currentItem = items.filter('.hide');
    var selectors;
    var nextItem = currentItem.first();

    if (nextItem.length) {
        currentItem = nextItem.removeClass('hide');

        // Disable options already active
        var integrationSelect = currentItem.find('select.integration-field').first();
        mQuery(currentItem).closest('.fields-container').find('.field-container:not(.hide) select').not(integrationSelect).find('option:selected').each(
            function() {
                var option = integrationSelect.find('option[value="'+mQuery(this).val()+'"]');
                mQuery(option).prop('disabled', true);
                mQuery(option).attr('disabled', 'disabled');
            }
        );

        // Select the first option that's not disabled
        mQuery(integrationSelect).val(mQuery('option:enabled', integrationSelect).first().val());

        currentItem.find('select').each(function () {
            mQuery(this).prop('disabled', false)
                .trigger('change')
                .trigger('chosen:updated');
        });

        currentItem.find('label').removeClass('disabled');
        currentItem.find('input[type="radio"]').prop('disabled', false).next().prop('disabled', false);
    }

    Mautic.stopIconSpinPostEvent();
};

Mautic.removePluginField = function (selector, indexClass) {
    var deleteCurrentItem = mQuery('#' + indexClass);

    deleteCurrentItem.find('input[type="radio"]').prop('disabled', true).next().prop('disabled', true);
    deleteCurrentItem.find('label').addClass('disabled');

    // Move the item to be the first hidden
    if (deleteCurrentItem.closest('.fields-container').find('.field-container.hide').length) {
        deleteCurrentItem.insertBefore(deleteCurrentItem.closest('.fields-container').find('.field-container.hide').first());
    } else {
        // There are no more so append to the end
        deleteCurrentItem.insertAfter(deleteCurrentItem.closest('.fields-container').find('.field-container').last());
    }

    // Add back the option to other selects
    var integrationSelect = deleteCurrentItem.find('select.integration-field').first();
    var groupSelects = mQuery(deleteCurrentItem).closest('.fields-container').find('select.integration-field').not(integrationSelect);
    mQuery('option[value="' + integrationSelect.val() + '"]', groupSelects).each(function() {
        if (!mQuery(this).closest('select').prop('disabled')) {
            mQuery(this).prop('disabled', false);
            mQuery(this).removeAttr('disabled');
        }
    });

    deleteCurrentItem.find('option').each(function() {
        mQuery(this).prop('disabled', false);
        mQuery(this).removeAttr('disabled');
    });

    groupSelects.each(function() {
        if (!mQuery(this).closest('.field-container').hasClass('hide')) {
            mQuery(this).trigger('change');
        }
    });

    deleteCurrentItem.addClass('hide');
    deleteCurrentItem.find('select').each(function( ) {
        mQuery( this ).prop('disabled', true).trigger("chosen:updated");
    });

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

Mautic.getIntegrationCampaignStatus = function (el, settings) {
    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));

    var data = {integration:mQuery('#campaignevent_properties_integration').val(),campaign: mQuery(el).val()};
    if(typeof mQuery('#campaignevent_properties_integration').val() == 'undefined') {
        data = {integration:mQuery('#formaction_properties_integration').val(),campaign: mQuery(el).val()};
    }

    mQuery('.integration-campaigns-status').html('');

    Mautic.ajaxActionRequest('plugin:getIntegrationCampaignStatus', data,
        function (response) {
            if (response.success) {
                mQuery('.integration-campaigns-status').append(response.html);
                Mautic.onPageLoad('.integration-campaigns-status', response);
            }

            Mautic.integrationConfigOnLoad('.integration-campaigns-status');
            Mautic.removeLabelLoadingIndicator();
        }
    );
};