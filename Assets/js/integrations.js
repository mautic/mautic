Mautic.integrationsConfigOnLoad = function () {
    // Hide the dismiss button, yes a hack
    mQuery('form[name=integration_config]').closest('.modal').find('button.close').hide();

    mQuery('.integration-keyword-filter').each(function() {
        var integration = mQuery(this).attr('data-integration');
        var object = mQuery(this).attr('data-object');
        mQuery(this).on("keydown", function (event) {
            if (event.which == 13) {
                Mautic.getPaginatedIntegrationFields(
                    {
                        'integration': integration,
                        'object': object,
                        'keyword': mQuery(this).val()
                    },
                    mQuery('#integration_config_featureSettings_sync_fieldMappings_' + object + '_filter-page').val(),
                    this
                );
            }
        });
    });
};

Mautic.getPaginatedIntegrationFields = function(settings, page, element) {
    var requestName = settings.integration + '-' + settings.object;
    var action = mauticBaseUrl + 's/integration/' + settings.integration + '/config/' + settings.object + '/' + page;
    if (settings.keyword) {
        action = action + '?keyword=' + settings.keyword;
    }

    if (typeof Mautic.activeActions == 'undefined') {
        Mautic.activeActions = {};
    } else if (typeof Mautic.activeActions[requestName] != 'undefined') {
        Mautic.activeActions[requestName].abort();
    }

    var object    = settings.object;
    var fieldsTab = '#field-mappings-'+object+'-container';

    if (element && mQuery(element).is('input')) {
        Mautic.activateLabelLoadingIndicator(mQuery(element).attr('id'));
    }
    var fieldsContainer = '#field-mappings-'+object;

    var modalId = '#'+mQuery(fieldsContainer).closest('.modal').attr('id');
    Mautic.startModalLoadingBar(modalId);

    Mautic.activeActions[requestName] = mQuery.ajax({
        showLoadingBar: false,
        url: action,
        type: "POST",
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery(fieldsContainer).html(response.html);
                Mautic.onPageLoad(fieldsContainer);
                Mautic.integrationConfigOnLoad(fieldsContainer);
                if (mQuery(fieldsTab).length) {
                    mQuery(fieldsTab).removeClass('hide');
                }
            } else if (mQuery(fieldsTab).length) {
                mQuery(fieldsTab).addClass('hide');
            }

            if (element) {
                Mautic.removeLabelLoadingIndicator();
            }

            Mautic.stopModalLoadingBar(modalId);
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function () {
            delete Mautic.activeActions[requestName]
        }
    });
};