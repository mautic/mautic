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