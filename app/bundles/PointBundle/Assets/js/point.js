//PointBundle
Mautic.pointOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'point');
    }

    if (mQuery(container + ' form[name="point"]').length) {
        Mautic.activateCategoryLookup('point', 'point');
    }

    if (mQuery('#point_actions')) {
        //make the fields sortable
        mQuery('#point_actions').sortable({
            items: '.point-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=point:reorderActions",
                    data: mQuery('#point_actions').sortable("serialize")});
            }
        });

        mQuery('#point_actions .point-row').on('mouseover.pointactions', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.pointactions', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }
};

Mautic.pointactionOnLoad = function (container, response) {
    //new action created so append it to the form
    if (response.actionHtml) {
        var newHtml = response.actionHtml;
        var actionId = '#point_action_' + response.actionId;
        if (mQuery(actionId).length) {
            //replace content
            mQuery(actionId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#point_actions');
            var newField = true;
        }
        //activate new stuff
        mQuery(actionId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        mQuery(actionId + " *[data-toggle='tooltip']").tooltip({html: true});

        mQuery('#point_actions .point-row').off(".point");
        mQuery('#point_actions .point-row').on('mouseover.pointactions', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.pointactions', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });

        //show actions panel
        if (!mQuery('#actions-panel').hasClass('in')) {
            mQuery('a[href="#actions-panel"]').trigger('click');
        }

        if (mQuery('#point-action-placeholder').length) {
            mQuery('#point-action-placeholder').remove();
        }
    }
};