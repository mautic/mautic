Mautic.getStageActionPropertiesForm = function(actionType) {
    Mautic.activateLabelLoadingIndicator('stage_type');

    var query = "action=stage:getActionForm&actionType=" + actionType;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                mQuery('#stageActionProperties').html(response.html);
                Mautic.onPageLoad('#stageActionProperties', response);
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