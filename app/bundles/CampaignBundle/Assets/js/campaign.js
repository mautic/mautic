//CampaignBundle

/**
 * Setup the campaign view
 *
 * @param container
 */
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

        mQuery('#campaignEvents .campaign-event-details').on('dblclick.campaignevents', function() {
            mQuery(this).find('.btn-edit').first().click();
        });
    }
};

/**
 * Setup the campaign event view
 *
 * @param container
 * @param response
 */
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

        //initialize ajax'd modals
        mQuery(eventId + " a[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        //initialize tooltips
        mQuery(eventId + " *[data-toggle='tooltip']").tooltip({html: true});

        mQuery(eventId).off('.campaignevents');
        mQuery(eventId).on('mouseover.campaignevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.campaignevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
        mQuery(eventId).on('dblclick.campaignevents', function() {
            mQuery(this).find('.btn-edit').first().click();
        });

        if (mQuery('#campaign-event-placeholder').length) {
            mQuery('#campaign-event-placeholder').remove();
        }
    }
};

/**
 * Change the links in the available event list when the campaign type is changed
 */
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

/**
 * Launch campaign builder modal
 */
Mautic.launchCampaignEditor = function() {
//    mQuery('#campaignBuilder').modal('show');
    mQuery('.page-builder').addClass('page-builder-active');
    //show it

    Mautic.drawCampaign();
    mQuery('.page-builder').removeClass('hide');
};

/**
 * Enable/Disable timeframe settings if the toggle for immediate trigger is changed
 */
Mautic.campaignToggleTimeframes = function() {
    var immediateChecked = mQuery('#campaignevent_triggerMode_0').prop('checked');
    var intervalChecked  = mQuery('#campaignevent_triggerMode_1').prop('checked');
    var dateChecked      = mQuery('#campaignevent_triggerMode_2').prop('checked');

    if (mQuery('#campaignevent_triggerInterval').length) {
        if (immediateChecked) {
            mQuery('#campaignevent_triggerInterval').attr('disabled', true);
            mQuery('#campaignevent_triggerIntervalUnit').attr('disabled', true);
            mQuery('#campaignevent_triggerDate').attr('disabled', true);
        } else if (intervalChecked) {
            mQuery('#campaignevent_triggerInterval').attr('disabled', false);
            mQuery('#campaignevent_triggerIntervalUnit').attr('disabled', false);
            mQuery('#campaignevent_triggerDate').attr('disabled', true);
        } else if (dateChecked) {
            mQuery('#campaignevent_triggerInterval').attr('disabled', true);
            mQuery('#campaignevent_triggerIntervalUnit').attr('disabled', true);
            mQuery('#campaignevent_triggerDate').attr('disabled', false);
        }
    }
};

