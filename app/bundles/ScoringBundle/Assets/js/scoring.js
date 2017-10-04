
Mautic.getScoringActionPropertiesForm = function(actionType) {
    Mautic.activateLabelLoadingIndicator('scoring_type');
    var query = "action=scoring:getActionForm&actionType=" + actionType;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                mQuery('#scoringActionProperties').html(response.html);
                Mautic.onPageLoad('#scoringActionProperties', response);
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

Mautic.onChangeUpdateGlobalScore = function(e) {
    var v = !!(1*e.value);
    if(v) {
        mQuery('#scoring_category_globalScoreModifier').removeClass('hide');
        mQuery('label[for="scoring_category_globalScoreModifier"]').removeClass('hide');
    } else {
        mQuery('#scoring_category_globalScoreModifier').addClass('hide');
        mQuery('label[for="scoring_category_globalScoreModifier"]').addClass('hide');
        mQuery('#scoring_category_globalScoreModifier').val('0.00');
    }
    return true;
};

