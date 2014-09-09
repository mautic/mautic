//PointBundle
Mautic.pointOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'point');
    }

    if (mQuery(container + ' form[name="point"]').length) {
        Mautic.activateCategoryLookup('point', 'point');
    }
};

Mautic.pointTriggerOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'point.trigger');
    }

    if (mQuery(container + ' form[name="pointtrigger"]').length) {
        Mautic.activateCategoryLookup('pointtrigger', 'point');
    }

    if (mQuery('#triggerEvents')) {
        //make the fields sortable
        mQuery('#triggerEvents').sortable({
            items: '.trigger-event-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=point:reorderTriggerEvents",
                    data: mQuery('#triggerEvents').sortable("serialize")});
            }
        });

        mQuery('#triggerEvents .trigger-event-row').on('mouseover.triggerevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.triggerevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }
};

Mautic.pointTriggerEventLoad = function (container, response) {
    //new action created so append it to the form
    if (response.actionHtml) {
        var newHtml = response.actionHtml;
        var actionId = '#triggerEvent' + response.actionId;
        if (mQuery(actionId).length) {
            //replace content
            mQuery(actionId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#triggerEvents');
            var newField = true;
        }
        //activate new stuff
        mQuery(actionId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        mQuery(actionId + " *[data-toggle='tooltip']").tooltip({html: true});

        mQuery('#triggerEvents .trigger-event-row').off(".triggerevents");
        mQuery('#triggerEvents .trigger-event-row').on('mouseover.triggerevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.triggerevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });

        //show events panel
        if (!mQuery('#events-panel').hasClass('in')) {
            mQuery('a[href="#events-panel"]').trigger('click');
        }

        if (mQuery('#trigger-event-placeholder').length) {
            mQuery('#trigger-event-placeholder').remove();
        }
    }
};

Mautic.getPointActionPropertiesForm = function(actionType) {
    var labelSpinner = mQuery("label[for='point_type']");
    var spinner = mQuery('<i class="fa fa-fw fa-spinner fa-spin"></i>');
    labelSpinner.append(spinner);
    var query = "action=point:getActionForm&actionType=" + actionType;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                mQuery('#pointActionProperties').html(response.html);
                Mautic.onPageLoad('#pointActionProperties', response);
            }
            spinner.remove();
        },
        error: function (request, textStatus, errorThrown) {
            if (mauticEnv == 'dev') {
                alert(errorThrown);
            }
            spinner.remove();
        }
    });
};