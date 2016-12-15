Mautic.getAbTestWinnerForm = function(bundle, formName, abKey) {
    if (abKey && mQuery(abKey).val() && mQuery(abKey).closest('.form-group').hasClass('has-error')) {
        mQuery(abKey).closest('.form-group').removeClass('has-error');
        if (mQuery(abKey).next().hasClass('help-block')) {
            mQuery(abKey).next().remove();
        }
    }

    Mautic.activateLabelLoadingIndicator(formName+'_variantSettings_winnerCriteria');
    var id    = mQuery('#'+formName+'_sessionId').val();
    var query = "action="+bundle+":getAbTestForm&abKey=" + mQuery(abKey).val() + "&id=" + id;

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                if (mQuery('#'+formName+'_variantSettings_properties').length) {
                    mQuery('#'+formName+'_variantSettings_properties').replaceWith(response.html);
                } else {
                    mQuery('#'+formName+'_variantSettings').append(response.html);
                }

                if (response.html != '') {
                    Mautic.onPageLoad('#'+formName+'_variantSettings_properties', response);
                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function() {
            Mautic.removeLabelLoadingIndicator();
        }
    });
};