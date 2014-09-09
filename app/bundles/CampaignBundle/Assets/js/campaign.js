//CampaignBundle

Mautic.campaignOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'campaign');
    }

    if (mQuery(container + ' form[name="campaign"]').length) {
        Mautic.activateCategoryLookup('campaign', 'campaign');
    }

    if (mQuery('#triggerEvents')) {
        //make the fields sortable
        mQuery('#triggerEvents').sortable({
            items: '.campaign-event-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=campaign:reorderCampaignEvents",
                    data: mQuery('#triggerEvents').sortable("serialize")});
            }
        });

        mQuery('#triggerEvents .campaign-event-row').on('mouseover.campaignevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.campaignevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }
};

Mautic.campaignEventLoad = function (container, response) {
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

        mQuery('#triggerEvents .campaign-event-row').off(".campaignevents");
        mQuery('#triggerEvents .campaign-event-row').on('mouseover.campaignevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.campaignevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });

        //show events panel
        if (!mQuery('#events-panel').hasClass('in')) {
            mQuery('a[href="#events-panel"]').campaign('click');
        }

        if (mQuery('#campaign-event-placeholder').length) {
            mQuery('#campaign-event-placeholder').remove();
        }
    }
};