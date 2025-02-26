//PageBundle
Mautic.pageOnLoad = function (container, response) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'page.page');
    }

    if (mQuery(container + ' #page_template').length) {
        Mautic.toggleBuilderButton(mQuery('#page_template').val() == '');

        // Preload tokens for code mode builder
        Mautic.getTokens(Mautic.getBuilderTokensMethod(), function(){});
        Mautic.initSelectTheme(mQuery('#page_template'));
    }

    // Open the builder directly when saved from the builder
    if (response && response.inBuilder) {
        Mautic.launchBuilder('page');
        Mautic.processBuilderErrors(response);
    }
};

Mautic.getPageAbTestWinnerForm = function(abKey) {
    if (abKey && mQuery(abKey).val() && mQuery(abKey).closest('.form-group').hasClass('has-error')) {
        mQuery(abKey).closest('.form-group').removeClass('has-error');
        if (mQuery(abKey).next().hasClass('help-block')) {
            mQuery(abKey).next().remove();
        }
    }

    Mautic.activateLabelLoadingIndicator('page_variantSettings_winnerCriteria');

    var pageId = mQuery('#page_sessionId').val();
    var query  = "action=page:getAbTestForm&abKey=" + mQuery(abKey).val() + "&pageId=" + pageId;

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                if (mQuery('#page_variantSettings_properties').length) {
                    mQuery('#page_variantSettings_properties').replaceWith(response.html);
                } else {
                    mQuery('#page_variantSettings').append(response.html);
                }

                if (response.html != '') {
                    Mautic.onPageLoad('#page_variantSettings_properties', response);
                }
            }

            Mautic.removeLabelLoadingIndicator();

        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
            spinner.remove();
        },
        complete: function () {
            Mautic.removeLabelLoadingIndicator();
        }
    });
};
