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

    if (mQuery('#CampaignEventPanel').length) {

        //activate jsPlumb
        jsPlumb.ready(function() {
            Mautic.campaignBuilderInstance = jsPlumb.getInstance({
                Container: document.getElementById("CampaignCanvas")
            });

            Mautic.updateCampaignConnections = function(info, remove) {
                remove = (remove) ? 1 : 0;

                var sourceId = info.connection.sourceId;
                var targetId = info.connection.targetId;
                var sourceEndpoint = info.sourceEndpoint.anchor.cssClass;
                var targetEndpoint = info.targetEndpoint.anchor.cssClass;

                var query = "action=campaign:updateConnections&source=" + sourceId + "&target=" + targetId + "&remove=" + remove + "&sourceEndpoint=" + sourceEndpoint + "&targetEndpoint=" + targetEndpoint;
                mQuery.ajax({
                    url: mauticAjaxUrl,
                    type: "POST",
                    data: query,
                    dataType: "json",
                    success: function (response) {

                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
            };

            Mautic.campaignBuilderInstance.bind("connection", function(info, originalEvent) {
                Mautic.updateCampaignConnections(info);
            });

            Mautic.campaignBuilderInstance.bind("connectionDetached", function(info, originalEvent) {
                if (typeof originalEvent !== 'undefined') {
                    Mautic.updateCampaignConnections(info, true);
                }
            });

            Mautic.campaignOverlayOptions = [
                 [ "Arrow", { width:20, length:20, location:0.5 } ]
            ];

            Mautic.campaignBuilderTopAnchor = [0.5, 0, 0, -1, 0, 0, "top" ];
            Mautic.campaignBuilderTopEndpoint = {
                endpoint: "Dot",
                paintStyle: {
                    width: 25,
                    height: 21,
                    fillStyle: "#d5d4d4"
                },
                connector:"Straight",
                connectorOverlays: Mautic.campaignOverlayOptions,
                isTarget: true,
                dropOptions: {
                    tolerance: "touch",
                    hoverClass: "dropHover",
                    activeClass: "dragActive"
                },
                beforeDrop: function(params) {
                    //ensure that a yes/no isn't looping back to this endpoint
                    var currentConnections = Mautic.campaignBuilderInstance.select({
                        source:params.targetId,
                        target:params.sourceId
                    });

                    if (currentConnections.length >= 1) {
                        return false;
                    }

                    //ensure that the connections are not looping back into the same event
                    return params.sourceId != params.targetId;
                }
            };

            Mautic.campaignBuilderBottomAnchor = [ 0.5, 1, 0, 1, 0, 0, "bottom" ];
            Mautic.campaignBuilderBottomEndpoint = {
                endpoint: "Dot",
                paintStyle: {
                    width: 25,
                    height: 21,
                    fillStyle: "#d5d4d4"
                },
                isSource: true,
                connectorStyle: {
                    strokeStyle: "#d5d4d4",
                    lineWidth: 4
                },
                connectorOverlays: Mautic.campaignOverlayOptions,
                connector:"Straight"
            };

            Mautic.campaignBuilderYesAnchor = [0, 1, 0, 1, 30, 0, "yes"];
            Mautic.campaignBuilderYesEndpoint = {
                endpoints: "Dot",
                paintStyle: {
                    width: 25,
                    height: 21,
                    fillStyle: "#00b49c"
                },
                isSource: true,
                connectorStyle: {
                    strokeStyle: "#00b49c",
                    lineWidth: 4
                },
                connector:"Straight",
                connectorOverlays: Mautic.campaignOverlayOptions
            };

            Mautic.campaignBuilderNoAnchor = [1, 1, 0, 1, -30, 0, "no"];
            Mautic.campaignBuilderNoEndpoint = {
                endpoint: "Dot",
                paintStyle: {
                    width: 25,
                    height: 21,
                    fillStyle: "#f86b4f"
                },
                isSource: true,
                connectorStyle: {
                    strokeStyle: "#f86b4f",
                    lineWidth: 4
                },
                connectorOverlays: Mautic.campaignOverlayOptions,
                connector:"Straight"
            };


            Mautic.campaignDragOptions = {
                containment:true,
                start: function(params) {
                    //double clicking activates the stop function so add a catch to prevent unnecessary ajax calls
                    this.startingPosition = mQuery(params.el).position();
                },
                stop: function(params) {
                    //use jQuery as well to ensure consistency with comparison
                    var endingPosition = mQuery(params.el).position();

                    if (this.startingPosition.left !== endingPosition.left || this.startingPosition.top !== endingPosition.top) {

                        //update coordinates
                        mQuery('#droppedX').val(params.pos[0]);
                        mQuery('#droppedY').val(params.pos[1]);

                        var query = "action=campaign:updateCoordinates&droppedX=" + params.pos[0] + "&droppedY=" + params.pos[1] + "&eventId=" + mQuery(params.el).attr('id');
                        mQuery.ajax({
                            url: mauticAjaxUrl,
                            type: "POST",
                            data: query,
                            dataType: "json",
                            success: function (response) {

                            },
                            error: function (request, textStatus, errorThrown) {
                                Mautic.processAjaxError(request, textStatus, errorThrown);
                            }
                        });
                    }
                }
            };

            Mautic.campaignBuilderInstance.doWhileSuspended(function() {
                //manually loop through each so a UUID can be set for reconnecting connections
                mQuery("#CampaignCanvas .list-campaign-event").each( function() {
                    var id = mQuery(this).attr('id');
                    Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(id), {anchor: Mautic.campaignBuilderTopAnchor, uuid: id + "_top"}, Mautic.campaignBuilderTopEndpoint);
                });

                mQuery("#CampaignCanvas .list-campaign-nondecision").each( function() {
                    var id = mQuery(this).attr('id');
                    Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(id), {anchor: Mautic.campaignBuilderBottomAnchor, uuid: id + "_bottom"}, Mautic.campaignBuilderBottomEndpoint);
                });

                mQuery("#CampaignCanvas .list-campaign-decision").each( function() {
                    var id = mQuery(this).attr('id');
                    Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(id), {anchor: Mautic.campaignBuilderYesAnchor, uuid: id + "_yes"}, Mautic.campaignBuilderYesEndpoint);
                    Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(id), {anchor: Mautic.campaignBuilderNoAnchor, uuid: id + "_no"}, Mautic.campaignBuilderNoEndpoint);
                });

                //enable drag and drop
                Mautic.campaignBuilderInstance.draggable(document.querySelectorAll("#CampaignCanvas .list-campaign-event"), Mautic.campaignDragOptions);

                //activate existing connections
                Mautic.campaignBuilderReconnectEndpoints();
            });
        });

        //update the coordinates on drop
        mQuery('.campaign-builder').droppable({
            drop: function (event, ui) {
                //update coordinates
                mQuery('#droppedX').val(ui.position.left);
                mQuery('#droppedY').val(ui.position.top);

                mQuery('#' + ui.draggable.attr('id')).click();
            },
            over: function (e, ui) {
                mQuery(this).addClass('over-droppable');
            },
            out: function (e, ui) {
                mQuery(this).removeClass('over-droppable');
            }
        });

        //make the events draggable
        mQuery('#CampaignEventPanel .list-group-item').draggable({
            helper: 'clone',
            appendTo: '.campaign-builder',
            zIndex: 8000,
            scroll: true,
            scrollSensitivity: 100,
            scrollSpeed: 100,
            cursorAt: {top: 15, left: 15}
        });

        // set hover and double click functions for the event buttons
        mQuery('#CampaignCanvas .list-group-item').off('.eventbuttons')
            .on('mouseover.eventbuttons', function() {
                mQuery(this).find('.campaign-event-buttons').removeClass('hide');
            })
            .on('mouseout.eventbuttons', function() {
                mQuery(this).find('.campaign-event-buttons').addClass('hide');
            })
            .on('dblclick.eventbuttons', function(event) {
                event.preventDefault();
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
        var domEventId = 'CampaignEvent_' + response.eventId;
        var eventId    = '#' + domEventId;

        if (mQuery(eventId).length) {
            //get the current position
            var position = mQuery(eventId).position();

            //replace content
            mQuery(eventId).replaceWith(newHtml);
            mQuery(eventId).css({
                left: position.left + "px",
                top: position.top + "px",
                position: "absolute"
            });

        } else {
            //append content
            var x = mQuery('#droppedX').val();
            var y = mQuery('#droppedY').val();

            mQuery(newHtml).appendTo('#CampaignCanvas');

            mQuery(eventId).css({'left': x + 'px', 'top': y + 'px'});

            if (response.eventType == 'leadaction') {
                Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(domEventId), {anchor: Mautic.campaignBuilderTopAnchor}, Mautic.campaignBuilderTopEndpoint);
                Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(domEventId), {anchor: Mautic.campaignBuilderYesAnchor}, Mautic.campaignBuilderYesEndpoint);
                Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(domEventId), {anchor: Mautic.campaignBuilderNoAnchor}, Mautic.campaignBuilderNoEndpoint);
            } else {
                Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(domEventId), {anchor: Mautic.campaignBuilderTopAnchor}, Mautic.campaignBuilderTopEndpoint);
                Mautic.campaignBuilderInstance.addEndpoint(document.getElementById(domEventId), {anchor: Mautic.campaignBuilderBottomAnchor}, Mautic.campaignBuilderBottomEndpoint);
            }

            var newField = true;
        }

        Mautic.campaignBuilderInstance.draggable(document.getElementById(domEventId), Mautic.campaignDragOptions);

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

        mQuery(eventId).off('.eventbuttons')
            .on('mouseover.eventbuttons', function() {
                mQuery(this).find('.campaign-event-buttons').removeClass('hide');
            })
            .on('mouseout.eventbuttons', function() {
                mQuery(this).find('.campaign-event-buttons').addClass('hide');
            })
            .on('dblclick.eventbuttons', function(event) {
                event.preventDefault();
                mQuery(this).find('.btn-edit').first().click();
            });

        //initialize tooltips
        mQuery(eventId + " *[data-toggle='tooltip']").tooltip({html: true});
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
    Mautic.stopIconSpinPostEvent();

    mQuery('.campaign-builder').addClass('campaign-builder-active');
    mQuery('.campaign-builder').removeClass('hide');
    Mautic.campaignBuilderInstance.repaintEverything();
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
            mQuery('#triggerInterval').addClass('hide');
            mQuery('#triggerDate').addClass('hide');
        } else if (intervalChecked) {
            mQuery('#triggerInterval').removeClass('hide');
            mQuery('#triggerDate').addClass('hide');
        } else if (dateChecked) {
            mQuery('#triggerInterval').addClass('hide');
            mQuery('#triggerDate').removeClass('hide');
        }
    }
};

Mautic.closeCampaignBuilder = function() {
    mQuery('.campaign-builder').addClass('hide');
};

Mautic.submitCampaignEvent = function(e) {
    e.preventDefault();

    mQuery('#campaignevent_canvasSettings_droppedX').val(mQuery('#droppedX').val());
    mQuery('#campaignevent_canvasSettings_droppedY').val(mQuery('#droppedY').val());
    mQuery('#campaignevent_canvasSettings_decisionPath').val(mQuery('#decisionPath').val());

    mQuery('form[name="campaignevent"]').submit();
};
