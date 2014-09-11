//CampaignBundle

Mautic.campaignOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'campaign');
    }

    if (mQuery(container + ' form[name="campaign"]').length) {
        Mautic.activateCategoryLookup('campaign', 'campaign');
    }

    if (mQuery('#campaignEvents').length) {
        //make the fields sortable
        mQuery('#campaignEvents').nestedSortable({
            items: 'li',
            handle: '.reorder-handle',
            toleranceElement: '> div',
            isTree: true,
            placeholder: "campaign-event-placeholder",
            helper: function() {
                return mQuery('<div><i class="fa fa-lg fa-crosshairs"></i></div>');
            },
            cursorAt: {top: 15, left: 15},
            tabSize: 10,
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=campaign:reorderCampaignEvents",
                    data: mQuery('#campaignEvents').nestedSortable("serialize")
                });
            }
        });

        mQuery('#campaignEvents .campaign-event-details').on('mouseover.campaignevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.campaignevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }
};

Mautic.campaignEventOnLoad = function (container, response) {
    //new action created so append it to the form
    if (response.eventHtml) {
        var newHtml = response.eventHtml;
        var eventId = '#CampaignEvent_' + response.eventId;
        if (mQuery(eventId).length) {
            //replace content
            mQuery(eventId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#campaignEvents');
            var newField = true;
        }
        //activate new stuff
        mQuery(eventId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        mQuery(eventId + " *[data-toggle='tooltip']").tooltip({html: true});

        mQuery('#campaignEvents .campaign-event-row').off(".campaignevents");
        mQuery('#campaignEvents .campaign-event-row').on('mouseover.campaignevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.campaignevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });

        //show events panel
        if (!mQuery('#events-panel').hasClass('in')) {
            mQuery('a[href="#events-panel"]').trigger('click');
        }

        if (mQuery('#campaign-event-placeholder').length) {
            mQuery('#campaign-event-placeholder').remove();
        }
    }
};

Mautic.updateCampaignEventLinks = function () {
    //find and update all the event links with the campaign type

    var campaignType = mQuery('#campaign_type .active input').val();
    if (typeof campaignType == 'undefined') {
        campaignType = 'interval';
    }

    mQuery('#campaignEventList a').each(function () {
        var href    = mQuery(this).attr('href');
        var newType = (campaignType == 'interval') ? 'date' : 'interval';

        href = href.replace('campaignType=' + campaignType, 'campaignType=' + newType);
        mQuery(this).attr('href', href);
    });
};