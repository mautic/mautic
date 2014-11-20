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

    if (mQuery('#CampaignEventPanel').length) {
        //update the coordinates on drop
        mQuery('#CampaignCanvas').droppable({
            drop: function (event, ui) {
                //update coordinates
                mQuery('#droppedX').val(ui.position.left);
                mQuery('#droppedY').val(ui.position.top);

                mQuery('#' + ui.draggable.attr('id')).click();
            }
        });

        //make the events draggable
        mQuery('#CampaignEventPanel .list-group-item').draggable({
            helper: 'clone',
            appendTo: '#CampaignCanvas',
            zIndex: 8000,
            scrollSensitivity: 100,
            scrollSpeed: 15,
            cursorAt: {top: 15, left: 15}
        });

        // set hover and double click functions for the event buttons
        mQuery('#CampaignCanvas .list-campaign-event').off('.eventbuttons')
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

    Mautic.renderCampaignViewsBarChart();
    Mautic.renderCampaignEmailSentPie();
    Mautic.renderCampaignLeadsBarChart();
};

/**
 * Delete the builder instance so it's regenerated when reopening the campaign event builder
 */
Mautic.campaignOnUnload = function(container) {
    delete Mautic.campaignBuilderInstance;
    if (container === '#app-content') {
        delete Mautic.campaignViewsBarChart;
        delete Mautic.campaignEmailSentPie;
        delete Mautic.campaignLeadsBarChart;
    }
}

/**
 * Setup the campaign event view
 *
 * @param container
 * @param response
 */
Mautic.campaignEventOnLoad = function (container, response) {
    //new action created so append it to the form
    if (response.deleted) {
        var domEventId = 'CampaignEvent_' + response.eventId;
        var eventId = '#' + domEventId;

        //remove the connections
        Mautic.campaignBuilderInstance.detachAllConnections(document.getElementById(domEventId));

        mQuery('.' + domEventId).each(function () {
            mQuery(this).remove();
        });

        //remove the div
        mQuery(eventId).remove();
    } else if (response.updateHtml) {
        var domEventId = 'CampaignEvent_' + response.eventId;
        var eventId    = '#' + domEventId;

        if (response.label) {
            Mautic.campaignBuilderUpdateLabel(domEventId, response.label);
        } else {
            Mautic.campaignBuilderUpdateLabel(domEventId, false);
        }

        mQuery(eventId + " .campaign-event-content").html(response.updateHtml);
    } else if (response.eventHtml) {
        var newHtml = response.eventHtml;
        var domEventId = 'CampaignEvent_' + response.eventId;
        var eventId    = '#' + domEventId;

        //append content
        var x = mQuery('#droppedX').val();
        var y = mQuery('#droppedY').val();

        mQuery(newHtml).appendTo('#CampaignCanvas');

        mQuery(eventId).css({'left': x + 'px', 'top': y + 'px'});

        if (response.eventType == 'decision') {
            var theAnchor = Mautic.campaignBuilderTopAnchor;
            theAnchor[6] = 'top ' + domEventId;
            Mautic.campaignBuilderInstance.addEndpoint(domEventId, {anchor: theAnchor, uuid: domEventId + "_top"}, Mautic.campaignBuilderTopEndpoint);
            var theAnchor = Mautic.campaignBuilderYesAnchor;
            theAnchor[6] = 'yes ' + domEventId;
            Mautic.campaignBuilderInstance.addEndpoint(domEventId, {anchor: theAnchor, uuid: domEventId + "_yes"}, Mautic.campaignBuilderYesEndpoint);
            var theAnchor = Mautic.campaignBuilderNoAnchor;
            theAnchor[6] = 'no ' + domEventId;
            Mautic.campaignBuilderInstance.addEndpoint(domEventId, {anchor: theAnchor, uuid: domEventId + "_no"}, Mautic.campaignBuilderNoEndpoint);
        } else {
            var theAnchor = Mautic.campaignBuilderTopAnchor;
            theAnchor[6] = 'top ' + domEventId;
            Mautic.campaignBuilderInstance.addEndpoint(domEventId, {anchor: theAnchor, uuid: domEventId + "_top"}, Mautic.campaignBuilderTopEndpoint);
            var theAnchor = Mautic.campaignBuilderBottomAnchor;
            theAnchor[6] = 'bottom ' + domEventId;
            Mautic.campaignBuilderInstance.addEndpoint(domEventId, {anchor: theAnchor, uuid: domEventId + "_bottom"}, Mautic.campaignBuilderBottomEndpoint);
        }


        Mautic.campaignBuilderInstance.draggable(domEventId, Mautic.campaignDragOptions);

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

    Mautic.campaignBuilderInstance.repaintEverything();
};

Mautic.campaignBuilderUpdateLabel = function (domEventId, theLabel) {
    var currentConnections = Mautic.campaignBuilderInstance.select({
        target: domEventId
    });
    if (currentConnections.length > 0) {
        currentConnections.each(function(conn) {

            //remove current label
            var overlays = conn.getOverlays();
            if (overlays.length > 0) {
                mQuery.each(overlays, function (index, overlay) {
                    if (overlay.type == 'Label') {
                        conn.removeOverlay(overlay.id);
                    }
                });
            }

            if (theLabel) {
                conn.addOverlay(["Label", {
                    label: theLabel,
                    location: 0.65,
                    cssClass: "_jsPlumb_label",
                    id: conn.sourceId + "_" + conn.targetId + "_connectionLabel"
                }]);
            }
        });
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

    mQuery('.builder').addClass('builder-active');
    mQuery('.builder').removeClass('hide');

    if (typeof Mautic.campaignBuilderInstance == 'undefined') {
        Mautic.campaignBuilderInstance = jsPlumb.getInstance({
            Container: document.querySelector("#CampaignCanvas")
        });

        Mautic.updateCampaignConnections = function (info, remove) {
            remove = (remove) ? 1 : 0;

            var sourceId = info.connection.sourceId;
            var targetId = info.connection.targetId;
            var sourceEndpoint = info.sourceEndpoint.anchor.cssClass;
            var targetEndpoint = info.targetEndpoint.anchor.cssClass;
            var campaignId     = mQuery('#campaignId').val();
            var query = "action=campaign:updateConnections&campaignId=" + campaignId + "&source=" + sourceId + "&target=" + targetId + "&remove=" + remove + "&sourceEndpoint=" + sourceEndpoint + "&targetEndpoint=" + targetEndpoint;
            mQuery.ajax({
                url: mauticAjaxUrl,
                type: "POST",
                data: query,
                dataType: "json",
                success: function (response) {
                    if (!remove && response.label) {
                        info.connection.addOverlay(["Label", {
                            label: response.label,
                            location: 0.65,
                            cssClass: '_jsPlumb_label',
                            id: sourceId + "_" + targetId + "_connectionLabel"
                        }]);
                    }
                },
                error: function (request, textStatus, errorThrown) {
                    Mautic.processAjaxError(request, textStatus, errorThrown);
                }
            });
        };

        Mautic.campaignBuilderInstance.bind("connection", function (info, originalEvent) {

            if (typeof originalEvent != 'undefined') {
                Mautic.updateCampaignConnections(info);
            }
        });

        Mautic.campaignBuilderInstance.bind("connectionDetached", function (info, originalEvent) {
            Mautic.updateCampaignConnections(info, true);
        });

        var overlayOptions = [["Arrow", {width: 15, length: 15, location: 0.5}]];
        var endpoint = "Dot";
        var connector = ["Bezier", {curviness: 25}];
        var connectorStyleLineWidth = 3;

        Mautic.campaignBuilderTopAnchor = [0.5, 0, 0, -1, 0, 0];
        Mautic.campaignBuilderTopEndpoint = {
            endpoint: endpoint,
            paintStyle: {
                fillStyle: "#d5d4d4"
            },
            connector: connector,
            connectorOverlays: overlayOptions,
            isTarget: true,
            beforeDrop: function (params) {
                //ensure that a yes/no isn't looping back to this endpoint
                var currentConnections = Mautic.campaignBuilderInstance.select({
                    source: params.targetId,
                    target: params.sourceId
                });

                if (currentConnections.length >= 1) {
                    return false;
                }

                //ensure that the connections are not looping back into the same event
                return params.sourceId != params.targetId;
            }
        };

        Mautic.campaignBuilderBottomAnchor = [0.5, 1, 0, 1, 0, 0];
        Mautic.campaignBuilderBottomEndpoint = {
            endpoint: endpoint,
            paintStyle: {
                fillStyle: "#d5d4d4"
            },
            connector: connector,
            connectorOverlays: overlayOptions,
            maxConnections: -1,
            isSource: true,
            connectorStyle: {
                strokeStyle: "#d5d4d4",
                lineWidth: connectorStyleLineWidth
            }
        };

        Mautic.campaignBuilderYesAnchor = [0, 1, 0, 1, 30, 0];
        Mautic.campaignBuilderYesEndpoint = {
            endpoint: endpoint,
            paintStyle: {
                fillStyle: "#00b49c"
            },
            connector: connector,
            connectorOverlays: overlayOptions,
            maxConnections: -1,
            isSource: true,
            connectorStyle: {
                strokeStyle: "#00b49c",
                lineWidth: connectorStyleLineWidth
            }
        };

        Mautic.campaignBuilderNoAnchor = [1, 1, 0, 1, -30, 0];
        Mautic.campaignBuilderNoEndpoint = {
            endpoint: endpoint,
            paintStyle: {
                fillStyle: "#f86b4f"
            },
            connector: connector,
            connectorOverlays: overlayOptions,
            maxConnections: -1,
            isSource: true,
            connectorStyle: {
                strokeStyle: "#f86b4f",
                lineWidth: connectorStyleLineWidth
            }
        };

        Mautic.campaignDragOptions = {
            start: function (params) {
                //double clicking activates the stop function so add a catch to prevent unnecessary ajax calls
                this.startingPosition = mQuery(params.el).position();
            },
            stop: function (params) {
                //use jQuery as well to ensure consistency with comparison
                var endingPosition = mQuery(params.el).position();

                if (this.startingPosition.left !== endingPosition.left || this.startingPosition.top !== endingPosition.top) {

                    //update coordinates
                    mQuery('#droppedX').val(params.pos[0]);
                    mQuery('#droppedY').val(params.pos[1]);
                    var campaignId = mQuery('#campaignId').val();
                    var query = "action=campaign:updateCoordinates&campaignId=" + campaignId + "&droppedX=" + params.pos[0] + "&droppedY=" + params.pos[1] + "&eventId=" + mQuery(params.el).attr('id');
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

        Mautic.campaignBuilderInstance.setSuspendDrawing(true);

        //manually loop through each so a UUID can be set for reconnecting connections
        mQuery("#CampaignCanvas .list-campaign-event").each(function () {
            var id = mQuery(this).attr('id');
            var theAnchor = Mautic.campaignBuilderTopAnchor;
            theAnchor[6] = 'top ' + id;
            Mautic.campaignBuilderInstance.addEndpoint(id, {
                anchor: theAnchor,
                uuid: id + "_top"
            }, Mautic.campaignBuilderTopEndpoint);
        });

        mQuery("#CampaignCanvas .list-campaign-nondecision").each(function () {
            var id = mQuery(this).attr('id');
            var theAnchor = Mautic.campaignBuilderBottomAnchor;
            theAnchor[6] = 'bottom ' + id;
            Mautic.campaignBuilderInstance.addEndpoint(id, {
                anchor: theAnchor,
                uuid: id + "_bottom"
            }, Mautic.campaignBuilderBottomEndpoint);
        });

        mQuery("#CampaignCanvas .list-campaign-decision").each(function () {
            var id = mQuery(this).attr('id');
            var theAnchor = Mautic.campaignBuilderYesAnchor;
            theAnchor[6] = 'yes ' + id;
            Mautic.campaignBuilderInstance.addEndpoint(id, {
                anchor: theAnchor,
                uuid: id + "_yes"
            }, Mautic.campaignBuilderYesEndpoint);
            var theAnchor = Mautic.campaignBuilderNoAnchor;
            theAnchor[6] = 'no ' + id;
            Mautic.campaignBuilderInstance.addEndpoint(id, {
                anchor: theAnchor,
                uuid: id + "_no"
            }, Mautic.campaignBuilderNoEndpoint);
        });

        //enable drag and drop
        Mautic.campaignBuilderInstance.draggable(document.querySelectorAll("#CampaignCanvas .list-campaign-event"), Mautic.campaignDragOptions);

        //activate existing connections
        Mautic.campaignBuilderReconnectEndpoints();

        Mautic.campaignBuilderInstance.setSuspendDrawing(false, true);

        mQuery('.builder-content').scroll(function() {
            Mautic.campaignBuilderInstance.repaintEverything();
        });
    } else {

        Mautic.campaignBuilderInstance.repaintEverything();
    }
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
    mQuery('.builder').addClass('hide');
};

Mautic.submitCampaignEvent = function(e) {
    e.preventDefault();

    mQuery('#campaignevent_canvasSettings_droppedX').val(mQuery('#droppedX').val());
    mQuery('#campaignevent_canvasSettings_droppedY').val(mQuery('#droppedY').val());
    mQuery('#campaignevent_canvasSettings_decisionPath').val(mQuery('#decisionPath').val());

    mQuery('form[name="campaignevent"]').submit();
};

Mautic.renderCampaignViewsBarChart = function (container) {
    if (!mQuery('#campaign-views-chart').length) {
        return;
    }
    chartData = mQuery.parseJSON(mQuery('#campaign-views-chart-data').text());
    if (typeof chartData.labels === "undefined") {
        return;
    }
    var ctx = document.getElementById("campaign-views-chart").getContext("2d");
    var options = {
         scaleShowGridLines : false,
         barShowStroke : false,
         barValueSpacing : 1,
         showScale: false,
         tooltipFontSize: 10,
         tooltipCaretSize: 0
    }
    if (typeof Mautic.campaignViewsBarChart === 'undefined') {
        Mautic.campaignViewsBarChart = new Chart(ctx).Bar(chartData, options);
    }
};

Mautic.renderCampaignLeadsBarChart = function (container) {
    if (!mQuery('#campaign-leads-chart').length) {
        return;
    }
    chartData = mQuery.parseJSON(mQuery('#campaign-leads-chart-data').text());
    if (typeof chartData.labels === "undefined") {
        return;
    }
    var ctx = document.getElementById("campaign-leads-chart").getContext("2d");
    var options = {
         scaleShowGridLines : false,
         barShowStroke : false,
         barValueSpacing : 1,
         showScale: false,
         tooltipFontSize: 10,
         tooltipCaretSize: 0
    }
    if (typeof Mautic.campaignLeadsBarChart === 'undefined') {
        Mautic.campaignLeadsBarChart = new Chart(ctx).Bar(chartData, options);
    }
};

Mautic.renderCampaignEmailSentPie = function () {
    // Initilize chart only for first time
    if (typeof Mautic.campaignEmailSentPie === 'object') {
        return;
    }
    var element = mQuery('#emails-sent-rate');
    if (!element.length) {
        return;
    }
    var options = {
        responsive: false,
        tooltipFontSize: 10,
        tooltipTemplate: "<%if (label){%><%}%><%= value %>x <%=label%>"};
    var timesOnSiteData = mQuery.parseJSON(mQuery('#emails-sent-data').text());
    var ctx = document.getElementById("emails-sent-rate").getContext("2d");
    Mautic.campaignEmailSentPie = new Chart(ctx).Pie(timesOnSiteData, options);
};
