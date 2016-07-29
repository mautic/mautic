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
        // set hover and double click functions for the event buttons

        mQuery('#CampaignCanvas .list-campaign-event, .list-campaign-source').off('.eventbuttons')
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

        mQuery('#CampaignEventSelector').on('chosen:showing_dropdown', function () {
            // Disable canvas scrolling
            mQuery('.builder-content').css('overflow', 'hidden');

            mQuery.each(['Source', 'Action', 'Decision', 'Condition'], function (key, group) {
                if (mQuery('#'+group+'Group').prop('disabled')) {
                    var sourceLabel = mQuery('#'+group+'Group').attr('label');
                    mQuery("#CampaignEventSelector_chosen li:contains('" + sourceLabel + "')").addClass('disabled-result');
                }
            });

            mQuery('#CampaignEventSelector option').each(function () {
                // Initiate a tooltip on each option since chosen doesn't copy over the data attributes
                var chosenOption = '#CampaignEventSelector_chosen .option_' +  mQuery(this).attr('id');
                mQuery(chosenOption).tooltip({html: true, container: 'body'});
            });
        });

        mQuery('#CampaignEventSelector').on('chosen:hiding_dropdown', function () {
            // Re-enable canvas scrolling
            mQuery('.builder-content').css('overflow', 'auto');

            mQuery('#CampaignEventSelector option').each(function () {
                // Initiate a tooltip on each option since chosen doesn't copy over the data attributes
                var chosenOption = '#CampaignEventSelector_chosen .option_' +  mQuery(this).attr('id');
                mQuery(chosenOption).tooltip('destroy');
            });
        });

        mQuery('#CampaignEventSelector').on('change', function() {
            // Hide events list

            if (!mQuery('#CampaignEvent_newsource').length) {
                // Hide the CampaignEventPanel if visible
                mQuery('#CampaignEventPanel').addClass('hide');
            }

            // Get the option clicked
            var option  = mQuery('#CampaignEventSelector option[value="' + mQuery(this).val() + '"]');
            var option  = mQuery('#CampaignEventSelector option[value="' + mQuery(this).val() + '"]');

            // Display the modal with form
            Mautic.ajaxifyModal(option);

            // Reset the dropdown
            mQuery(this).val('');
            mQuery('#CampaignEventSelector').trigger('chosen:updated');
        });

        mQuery('#CampaignCanvas').on('click', function() {
            if (!mQuery('#CampaignEvent_newsource').length) {
                // Hide the CampaignEventPanel if visible
                mQuery('#CampaignEventPanel').addClass('hide');
            }
        })
    }
};

/**
 * Delete the builder instance so it's regenerated when reopening the campaign event builder
 */
Mautic.campaignOnUnload = function(container) {
    delete Mautic.campaignBuilderInstance;
    delete Mautic.campaignBuilderLabels;
}

/**
 * Setup the campaign event view
 *
 * @param container
 * @param response
 */
Mautic.campaignEventOnLoad = function (container, response) {
    //new action created so append it to the form
    var domEventId = 'CampaignEvent_' + response.eventId;
    var eventId = '#' + domEventId;

    Mautic.campaignBuilderLabels[domEventId] = (response.label) ? response.label : '';
    Mautic.campaignBuilderUpdateLabel(domEventId);

    if (response.deleted) {
        Mautic.campaignBuilderInstance.remove(document.getElementById(domEventId));
        delete Mautic.campaignBuilderEventPositions[domEventId];

    } else if (response.updateHtml) {

        mQuery(eventId + " .campaign-event-content").html(response.updateHtml);
    } else if (response.eventHtml) {
        var newHtml = response.eventHtml;

        //append content
        var x = parseInt(mQuery('#droppedX').val());
        var y = parseInt(mQuery('#droppedY').val());

        Mautic.campaignBuilderEventPositions[domEventId] = {
            'left': x,
            'top': y
        };

        mQuery(newHtml).appendTo('#CampaignCanvas');

        mQuery(eventId).css({'left': x + 'px', 'top': y + 'px'});

        if (response.eventType == 'decision' || response.eventType == 'condition') {
            Mautic.campaignBuilderRegisterAnchors(['top', 'yes', 'no'], eventId);
        } else {
            Mautic.campaignBuilderRegisterAnchors(['top', 'bottom'], eventId);
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

        // Connect into last anchor clicked
        Mautic.campaignBuilderInstance.connect({
            uuids: [
                Mautic.campaignBuilderAnchorClicked,
                domEventId+'_top'
            ]
        });
    }

    Mautic.campaignBuilderInstance.repaintEverything();
};

/**
 * Setup the campaign source view
 *
 * @param container
 * @param response
 */
Mautic.campaignSourceOnLoad = function (container, response) {
    //new action created so append it to the form
    var domEventId = 'CampaignEvent_' + response.sourceType;
    var eventId = '#' + domEventId;

    if (response.deleted) {
        Mautic.campaignBuilderInstance.remove(document.getElementById(domEventId));
        delete Mautic.campaignBuilderEventPositions[domEventId];

        mQuery('#campaignLeadSource_' + response.sourceType).prop('disabled', false);
        mQuery('#CampaignEventSelector').trigger('chosen:updated');

        // Check to see if all sources have been deleted
        if (!mQuery('#list-campaign-source:not(#CampaignEvent_newsource_hide)').length) {
            mQuery('#CampaignEvent_newsource_hide').attr('id', 'CampaignEvent_newsource');
            Mautic.campaignBuilderPrepareNewSource();
        }

    } else if (response.updateHtml) {

        mQuery(eventId + " .campaign-event-content").html(response.updateHtml);
    } else if (response.sourceHtml) {
        mQuery('#campaignLeadSource_' + response.sourceType).prop('disabled', true);
        mQuery('#CampaignEventSelector').trigger('chosen:updated');

        var newHtml = response.sourceHtml;

        if (mQuery('#CampaignEvent_newsource').length) {
            var x = mQuery('#CampaignEvent_newsource').position().left;
            var y = mQuery('#CampaignEvent_newsource').position().top;

            mQuery('#CampaignEvent_newsource').attr('id', 'CampaignEvent_newsource_hide');

            mQuery('#CampaignEventPanel').addClass('hide');
        } else {
            //append content
            var x = parseInt(mQuery('#droppedX').val());
            var y = parseInt(mQuery('#droppedY').val());
        }

        mQuery(newHtml).appendTo('#CampaignCanvas');
        Mautic.campaignBuilderEventPositions[domEventId] = {
            'left': x,
            'top': y
        };
        mQuery(eventId).css({'left': x + 'px', 'top': y + 'px'});

        Mautic.campaignBuilderRegisterAnchors(['leadSource', 'leadSourceLeft', 'leadSourceRight'], eventId);
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

        //initialize tooltipslist-campaign-event
        mQuery(eventId + " *[data-toggle='tooltip']").tooltip({html: true});

        // Connect into last anchor clicked
        Mautic.campaignBuilderInstance.connect({
            uuids: [
                Mautic.campaignBuilderAnchorClicked,
                domEventId+(Mautic.campaignBuilderAnchorClicked.search('left') ? '_leadsourceright' : '_leadsourceright')
            ]
        });
    }

    Mautic.campaignBuilderInstance.repaintEverything();
};

/**
 * Update a connectors label
 *
 * @param domEventId
 */
Mautic.campaignBuilderUpdateLabel = function (domEventId) {
    var theLabel = typeof Mautic.campaignBuilderLabels[domEventId] == 'undefined' ? '' : Mautic.campaignBuilderLabels[domEventId];
    var currentConnections = Mautic.campaignBuilderInstance.select({
        target: domEventId
    });

    if (currentConnections.length > 0) {
        currentConnections.each(function(conn) {

            //remove current label
            var overlays = conn.getOverlays();
            if (overlays.length > 0) {
                for (var i = 0; i <= overlays.length; i++ ) {
                    if ( typeof overlays[i] != 'undefined' && overlays[i].type == 'Label') {
                        conn.removeOverlay(overlays[i].id);
                    }
                }
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
    mQuery('body').css('overflow-y', 'hidden');

    mQuery('.builder').addClass('builder-active');
    mQuery('.builder').removeClass('hide');

    // Center new source
    if (mQuery('#CampaignEvent_newsource').length) {
        Mautic.campaignBuilderPrepareNewSource();
    }

    if (typeof Mautic.campaignBuilderInstance == 'undefined') {
        // Store labels
        Mautic.campaignBuilderLabels = {};

        Mautic.campaignBuilderInstance = jsPlumb.getInstance({
            Container: document.querySelector("#CampaignCanvas")
        });

        // Update the labels on connection/disconnection
        Mautic.campaignBuilderInstance.bind("connection", function (info, originalEvent) {
            Mautic.campaignBuilderUpdateLabel(info.connection.targetId);

            info.targetEndpoint.setPaintStyle(
                {
                    fillStyle: info.sourceEndpoint.getPaintStyle().fillStyle
                }
            );
        });

        Mautic.campaignBuilderInstance.bind("connectionDetached", function (info, originalEvent) {
            Mautic.campaignBuilderUpdateLabel(info.connection.targetId);

            info.targetEndpoint.setPaintStyle(
                {
                    fillStyle: "#d5d4d4"
                }
            );
        });

        Mautic.campaignBuilderInstance.bind("connectionMoved", function (info, originalEvent) {
            Mautic.campaignBuilderUpdateLabel(info.connection.originalTargetId);

            info.originalTargetEndpoint.setPaintStyle(
                {
                    fillStyle: "#d5d4d4"
                }
            );

            Mautic.campaignBuilderUpdateLabel(info.connection.newTargetId);

            info.newTargetEndpoint.setPaintStyle(
                {
                    fillStyle: info.newSourceEndpoint.getPaintStyle().fillStyle
                }
            );
        });

        Mautic.campaignBuilderConnectionsMap = {
            // source
            'source': {
                // source anchors
                'leadsource': {
                    // target
                    'source': [],
                    'action': ['top'], // target anchors
                    'condition': ['top'],
                    'decision': ['top'],
                },
                'leadsourceleft': {
                    'source': ['leadsourceright'],
                    'action': [],
                    'condition': [],
                    'decision': []
                },
                'leadsourceright': {
                    'source': ['leadsourceleft'],
                    'action': [],
                    'condition': [],
                    'decision': []
                }
            },
            'action': {
                'top': {
                    'source': ['leadsource'],
                    'action': [],
                    'condition': ['yes', 'no'],
                    'decision': ['yes', 'no']
                },
                'bottom': {
                    'source': [],
                    'action': [],
                    'condition': ['top'],
                    'decision': ['top']
                }
            },
            'condition': {
                'top': {
                    'source': ['leadsource'],
                    'action': ['bottom'],
                    'condition': ['yes', 'no'],
                    'decision': ['yes', 'no']
                },
                'yes': {
                    'source': [],
                    'action': ['top'],
                    'condition': ['top'],
                    'decision': ['top']
                },
                'no': {
                    'source': [],
                    'action': ['top'],
                    'condition': ['top'],
                    'decision': ['top']
                }
            },
            'decision': {
                'top': {
                    'action': ['bottom'],
                    'source': ['leadsource'],
                    'condition': ['yes', 'no'],
                    'decision': [],
                },
                'yes': {
                    'source': [],
                    'action': ['top'],
                    'condition': ['top'],
                    'decision': [],
                },
                'no': {
                    'source': [],
                    'action': ['top'],
                    'condition': ['top'],
                    'decision': [],
                }
            }
        };

        Mautic.campaignAnchors = {
            'top': [0.5, 0, 0, -1, 0, 0],
            'bottom': [0.5, 1, 0, 1, 0, 0],
            'yes': [0, 1, 0, 1, 30, 0],
            'no': [1, 1, 0, 1, -30, 0],
            'leadSource': [0.5, 1, 0, 1, 0, 0],
            'leadSourceLeft': [0, 0.5, -1, 0, -1, 0],
            'leadSourceRight': [1, 0.5, 1, 0, 1, 0],
        }

        Mautic.campaignEndpoints = {};
        Mautic.campaignBuilderRegisterEndpoint('top', '#d5d4d4', true);
        Mautic.campaignBuilderRegisterEndpoint('bottom', '#d5d4d4');
        Mautic.campaignBuilderRegisterEndpoint('yes', '#00b49c');
        Mautic.campaignBuilderRegisterEndpoint('no', '#f86b4f');
        Mautic.campaignBuilderRegisterEndpoint('leadSource', '#d5d4d4');
        Mautic.campaignBuilderRegisterEndpoint('leadSourceLeft', '#fdb933');
        Mautic.campaignBuilderRegisterEndpoint('leadSourceRight', '#fdb933');

        Mautic.campaignDragOptions = {
            start: function (event, ui) {
                //double clicking activates the stop function so add a catch to prevent unnecessary ajax calls
                this.startingPosition = ui.position;
            },
            stop: function (event, ui) {
                var endingPosition = ui.position;
                if (this.startingPosition.left !== endingPosition.left || this.startingPosition.top !== endingPosition.top) {
                    //update coordinates
                    Mautic.campaignBuilderEventPositions[mQuery(event.target).attr('id')] = {
                        'left': endingPosition.left,
                        'top': endingPosition.top
                    };

                    var campaignId = mQuery('#campaignId').val();
                    var query = "action=campaign:updateCoordinates&campaignId=" + campaignId + "&droppedX=" + endingPosition.top + "&droppedY=" + endingPosition.left + "&eventId=" + mQuery(event.target).attr('id');
                    mQuery.ajax({
                        url: mauticAjaxUrl,
                        type: "POST",
                        data: query,
                        dataType: "json",
                        error: function (request, textStatus, errorThrown) {
                            Mautic.processAjaxError(request, textStatus, errorThrown);
                        }
                    });
                }
            },
            scroll: true,
            scrollSensitivity: 100,
            scrollSpeed: 15,
            appendTo: '#CampaignCanvas',
            zIndex: 8000,
            cursorAt: {top: 15, left: 15}
        };

        Mautic.campaignBuilderInstance.setSuspendDrawing(true);

        //manually loop through each so a UUID can be set for reconnecting connections
        mQuery("#CampaignCanvas div.list-campaign-event").each(function () {
            Mautic.campaignBuilderRegisterAnchors(['top'], this);
        });

        mQuery("#CampaignCanvas div.list-campaign-event").not('.list-campaign-decision').not('.list-campaign-condition').not('#CampaignEvent_newsource').not('#CampaignEvent_newsource_hide').each(function () {
            Mautic.campaignBuilderRegisterAnchors(['bottom'], this);
        });

        mQuery("#CampaignCanvas div.list-campaign-decision, #CampaignCanvas div.list-campaign-condition").each(function () {
            Mautic.campaignBuilderRegisterAnchors(['yes', 'no'], this);
        });

        mQuery("#CampaignCanvas div.list-campaign-leadsource").not('#CampaignEvent_newsource').not('#CampaignEvent_newsource_hide').each(function () {
            Mautic.campaignBuilderRegisterAnchors(['leadSource', 'leadSourceLeft', 'leadSourceRight'], this);
        });

        //enable drag and drop
        Mautic.campaignBuilderInstance.draggable(
            document.querySelectorAll("#CampaignCanvas .draggable"),
            Mautic.campaignDragOptions
        );

        //activate existing connections
        Mautic.campaignBuilderEventDimensions = {
            'width': 200,
            'height': 45,
            'anchor': 10,
            'wiggle': 20
        };

        Mautic.campaignBuilderAnchorClicked = false;
        Mautic.campaignBuilderEventPositions = {};
        Mautic.campaignBuilderReconnectEndpoints();

        Mautic.campaignBuilderInstance.setSuspendDrawing(false, true);

        mQuery('.builder-content').scroll(function() {
            Mautic.campaignBuilderInstance.repaintEverything();
        });
    } else {

        Mautic.campaignBuilderInstance.repaintEverything();
    }
};

Mautic.campaignBeforeDropCallback = function(params) {
    var sourceEndpoint = Mautic.campaignBuilderGetEndpointDetails(params.connection.endpoints[0]);
    var targetEndpoint = Mautic.campaignBuilderGetEndpointDetails(params.dropEndpoint);

    if ('top' == targetEndpoint.anchorName) {
        //ensure that a top only has one connection at a time unless connected from a source
        if (sourceEndpoint.eventType != 'source' && params.dropEndpoint.connections.length > 0) {

            return false;
        }

        //ensure two events aren't looping
        var sourceConnections = Mautic.campaignBuilderInstance.select({
            source: params.targetId
        });
        var loopDetected = false;
        sourceConnections.each(function (conn) {

            if (conn.sourceId == targetEndpoint.elementId && conn.targetId == sourceEndpoint.elementId) {
                loopDetected = true;

                return false;
            }
        });

        if (loopDetected) {
            return false;
        }
    }

    //ensure that the connections are not looping back into the same event
    if (params.sourceId == params.targetId) {

        return false;
    }

    // ensure the map allows this connection
    var allowedConnections = Mautic.campaignBuilderConnectionsMap[sourceEndpoint.eventType][sourceEndpoint.anchorName][targetEndpoint.eventType];

    return mQuery.inArray(targetEndpoint.anchorName, allowedConnections) !== -1;
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
    var builderCss = {
        margin: "0",
        padding: "0",
        border: "none",
        width: "100%",
        height: "100%"
    };

    var panelHeight = (mQuery('.builder-content').css('right') == '0px') ? mQuery('.builder-panel').height() : 0,
        panelWidth = (mQuery('.builder-content').css('right') == '0px') ? 0 : mQuery('.builder-panel').width(),
        spinnerLeft = (mQuery(window).width() - panelWidth - 60) / 2,
        spinnerTop = (mQuery(window).height() - panelHeight - 60) / 2;

    var overlay     = mQuery('<div id="builder-overlay" class="modal-backdrop fade in"><div style="position: absolute; top:' + spinnerTop + 'px; left:' + spinnerLeft + 'px" class=".builder-spinner"><i class="fa fa-spinner fa-spin fa-5x"></i></div></div>').css(builderCss).appendTo('.builder-content');
    var nodes       = [];

    mQuery("#CampaignCanvas .list-campaign-event").each(function (idx, elem) {
        nodes.push({
            id:        mQuery(elem).attr('id').replace('CampaignEvent_', ''),
            positionX: parseInt(mQuery(elem).css('left'), 10),
            positionY: parseInt(mQuery(elem).css('top'), 10)
        });
    });

    mQuery("#CampaignCanvas .list-campaign-source").not('#CampaignEvent_newsource').not('#CampaignEvent_newsource_hide').each(function (idx, elem) {
        nodes.push({
            id:        mQuery(elem).attr('id').replace('CampaignEvent_', ''),
            positionX: parseInt(mQuery(elem).css('left'), 10),
            positionY: parseInt(mQuery(elem).css('top'), 10)
        });
    });

    var connections = [];
    mQuery.each(Mautic.campaignBuilderInstance.getConnections(), function (idx, connection) {
        connections.push({
            sourceId:     connection.sourceId.replace('CampaignEvent_', ''),
            targetId:     connection.targetId.replace('CampaignEvent_', ''),
            anchors:      mQuery.map(connection.endpoints, function (endpoint) {
                var anchor = Mautic.campaignBuilderGetEndpointDetails(endpoint);
                return {
                    'endpoint': anchor.anchorName,
                    'eventId':  anchor.eventId
                };
            })
        });
    });

    var chart          = {};
    chart.nodes        = nodes;
    chart.connections  = connections;
    var canvasSettings = {canvasSettings: chart};

    var campaignId     = mQuery('#campaignId').val();
    var query          = "action=campaign:updateConnections&campaignId=" + campaignId;

    mQuery('.btn-close-builder').prop('disabled', true);
    mQuery.ajax({
        url: mauticAjaxUrl + '?' + query,
        type: "POST",
        data: canvasSettings,
        dataType: "json",
        success: function (response) {
            mQuery('#builder-overlay').remove();
            mQuery('body').css('overflow-y', '');
            if (response.success) {
                mQuery('.builder').addClass('hide');
            }
            mQuery('.btn-close-builder').prop('disabled', false);
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
            mQuery('body').css('overflow-y', '');
        }
    });
};

Mautic.submitCampaignEvent = function(e) {
    e.preventDefault();

    mQuery('#campaignevent_canvasSettings_droppedX').val(mQuery('#droppedX').val());
    mQuery('#campaignevent_canvasSettings_droppedY').val(mQuery('#droppedY').val());
    mQuery('#campaignevent_canvasSettings_decisionPath').val(mQuery('#decisionPath').val());

    mQuery('form[name="campaignevent"]').submit();
};

Mautic.submitCampaignSource = function(e) {
    e.preventDefault();

    mQuery('#campaign_leadsource_droppedX').val(mQuery('#droppedX').val());
    mQuery('#campaign_leadsource_droppedY').val(mQuery('#droppedY').val());

    mQuery('form[name="campaign_leadsource"]').submit();
};

Mautic.campaignBuilderRegisterEndpoint = function (name, color, isTarget) {
    var isSource = true;
    if (typeof isTarget == 'undefined') {
        isTarget = false;
    }
    if (isTarget) {
        isSource = false;
    }

    Mautic.campaignEndpoints[name] = {
        endpoint: ["Dot", { radius: 10 }],
        paintStyle: {
            fillStyle: color
        },
        connector: ["Bezier", {curviness: 25}],
        connectorOverlays: [["Arrow", {width: 8, length: 8, location: 0.5}]],
        maxConnections: -1,
        isTarget: isTarget,
        isSource: isSource,
        connectorStyle: {
            strokeStyle: color,
            lineWidth: 1
        },
        beforeDrop: Mautic.campaignBeforeDropCallback
    }
};

Mautic.campaignBuilderRegisterAnchors = function(names, el) {
    var id = mQuery(el).attr('id');

    mQuery(names).each(function(key, anchorName) {
        var theAnchor = Mautic.campaignAnchors[anchorName];
        theAnchor[6] = anchorName.toLowerCase() + ' ' + id;
        var ep = Mautic.campaignBuilderInstance.addEndpoint(
            id,
            {
                anchor: theAnchor,
                uuid: id + "_" + anchorName.toLowerCase()
            },
            Mautic.campaignEndpoints[anchorName]
        );

        ep.bind("mouseover", function (endpoint, event) {
            var epDetails = Mautic.campaignBuilderGetEndpointDetails(endpoint);
            if (epDetails.anchorName == 'top') {
                // Don't add a plus sign to a top anchor
                return;
            }

            var dot = mQuery(endpoint.canvas);
            dot.addClass('_jsPlumb_clickable_anchor');

            if (!dot.find('svg text').length) {
                // Add a plus sign to SVG
                var svg = dot.find('svg')[0];

                var textElement = document.createElementNS("http://www.w3.org/2000/svg", 'text');
                textElement.setAttributeNS(null, 'x', '50%');
                textElement.setAttributeNS(null, 'y', '50%');
                textElement.setAttributeNS(null, 'text-anchor', 'middle');
                textElement.setAttributeNS(null, 'stroke-width', '2px');
                textElement.setAttributeNS(null, 'stroke', '#ffffff');
                textElement.setAttributeNS(null, 'dy', '.3em');

                var textNode = document.createTextNode('+');
                textElement.appendChild(textNode);
                svg.appendChild(textElement);
            }
        });

        ep.bind("mouseout", function (endpoint) {
            var dot = mQuery(endpoint.canvas);
            dot.removeClass('_jsPlumb_clickable_anchor');
        });

        ep.bind("click", function (endpoint, event) {
            if (mQuery('#CampaignEvent_newsource').length) {
                // Can't do anything until a new source is added

                return;
            }

            var epDetails = Mautic.campaignBuilderGetEndpointDetails(endpoint);
            if (epDetails.anchorName == 'top') {
                // Don't do anything for top anchors

                return;
            }

            var el    = mQuery(event.target).parents('._jsPlumb_endpoint')[0];

            var epPos = Mautic.campaignBuilderGetEventPosition(el);
            mQuery('#CampaignEventPanel').css({
                left: epPos.left,
                top: epPos.top
            });

            // Note the anchor so it can be auto attached after the event is created
            var epDetails = Mautic.campaignBuilderGetEndpointDetails(endpoint);
            var clickedAnchorName = epDetails.anchorName;
            Mautic.campaignBuilderAnchorClicked = endpoint.elementId+'_'+clickedAnchorName;

            // Get the position of the event
            var elPos = Mautic.campaignBuilderGetEventPosition(endpoint.element)
            var spotFound = false,
                putLeft = elPos.left,
                putTop = elPos.top,
                direction = '', // xl, xr, yu, yd
                fullWidth = Mautic.campaignBuilderEventDimensions.width + Mautic.campaignBuilderEventDimensions.anchor,
                wiggleWidth = fullWidth + Mautic.campaignBuilderEventDimensions.wiggle,
                fullHeight = Mautic.campaignBuilderEventDimensions.height + Mautic.campaignBuilderEventDimensions.anchor,
                wiggleHeight = fullHeight + Mautic.campaignBuilderEventDimensions.wiggle
                debug = false;

            if (debug) {
                console.log(Mautic.campaignBuilderEventPositions);
                console.log(clickedAnchorName+' - starting with: x = '+ putLeft+', y = '+putTop);
            }

            switch (clickedAnchorName) {
                case 'leadsourceleft':
                    direction = 'xl';
                    putLeft -= wiggleWidth;
                    break;
                case 'leadsourceright':
                    direction = 'xr';
                    putLeft += wiggleWidth;
                    break;
                case 'bottom':
                    direction = 'yd';
                    putTop += wiggleHeight;
                    break;
                case 'yes':
                case 'leadsource':
                    // Place slightly to the left of the anchor
                    putLeft -= Mautic.campaignBuilderEventDimensions.width/2;
                    putTop  += wiggleHeight;
                    direction = 'xl';
                    break;
                case 'no':
                    // Place slightly to the left of the anchor
                    putLeft += Mautic.campaignBuilderEventDimensions.width/2;
                    putTop  += wiggleHeight;
                    direction = 'xr';
                    break;
                case 'top':
                    directon = 'yu';
                    putTop -= wiggleHeight;
                    break;
            }

            if (debug) {
                console.log('Going direction: '+direction);
                console.log('Start test with: x = '+ putLeft+', y = '+putTop);

            }

            var counter = 0;
            var windowWidth = mQuery(window).width();
            while (!spotFound) {
                // Find out if spot is taken
                var isOccupied = false;
                mQuery.each(Mautic.campaignBuilderEventPositions, function (id, pos) {
                    var l = Math.max(putLeft, pos.left);
                    var r = Math.min(putLeft + fullWidth, pos.left + fullWidth);
                    var b = Math.max(putTop, pos.top);
                    var t = Math.min(putTop + fullHeight, pos.top + fullHeight);
                    var h = t-b;
                    var w = r-l;
                    if (debug) {
                        console.log('Checking ' + id);
                        console.log(putLeft, putTop, l,r,b,t,h,w);
                    }

                    if (h > 0 && w > 0) {
                        if (debug) {
                            console.log('Slot occupied');
                        }

                        isOccupied = true;

                        // xl, xr, yu, yd
                        switch (direction) {
                            case 'xl':
                                putLeft -= (w + Mautic.campaignBuilderEventDimensions.wiggle);
                                if (putLeft <= 0) {
                                    putLeft = 0;
                                    // Ran out of room so go down
                                    direction = 'yd';
                                    putTop += fullHeight + Mautic.campaignBuilderEventDimensions.wiggle;
                                }
                                break;
                            case 'xr':
                                if (putLeft + w + Mautic.campaignBuilderEventDimensions.wiggle > windowWidth) {
                                    // Hit right canvas so start going down by default
                                    direction = 'yd';
                                    putLeft -= Mautic.campaignBuilderEventDimensions.wiggle;
                                    putTop += fullHeight + Mautic.campaignBuilderEventDimensions.wiggle;
                                } else {
                                    putLeft += (w + Mautic.campaignBuilderEventDimensions.wiggle);
                                }
                                break;
                            case 'yu':
                                putTop -= (h - Mautic.campaignBuilderEventDimensions.wiggle);
                                if (putTop <= 0) {
                                    putTop = 0;
                                    // Ran out of room going up so try the right
                                    direction = 'xr';
                                }
                                break;
                            case 'yd':
                                putTop += (h + Mautic.campaignBuilderEventDimensions.wiggle);
                                break;
                        }

                        return false
                    }
                });

                if (!isOccupied) {
                    if (debug) {
                       console.log('It fits!');
                    }

                    spotFound = true;
                }

                counter++;
                if (counter >= 100) {
                    putTop = 10;
                    putLeft = 10;
                    if (debug) {
                        console.log('Too many loops');
                    }
                    spotFound = true;
                }
            }

            if (debug) {
                console.log('To be placed at: x = '+ putLeft+', y = '+putTop);
            }

            if (putLeft <= 0) {
                putLeft = 10;
            }
            if (putTop <= 0) {
                putTop = 10;
            }

            // Mark the spot
            mQuery('#droppedX').val(putLeft);
            mQuery('#droppedY').val(putTop);

            // Update the event selector
            var eventType = epDetails.eventType;

            var allowedEvents = [];
            mQuery.each(Mautic.campaignBuilderConnectionsMap[epDetails.eventType][epDetails.anchorName], function (group, eventTypes) {
                if (eventTypes.length) {
                    allowedEvents[allowedEvents.length] = group.charAt(0).toUpperCase() + group.substr(1);
                }
            });

            Mautic.campaignBuilderUpdateEventList(allowedEvents, false);

            mQuery('#CampaignEventPanel').removeClass('hide');
            setTimeout(function () {
                mQuery('#CampaignEventSelector').trigger('chosen:open');
            }, 100);
        });
    });
};

Mautic.campaignBuilderGetEventPosition = function(el) {
    return {
        'left': parseInt(mQuery(el).css('left')),
        'top': parseInt(mQuery(el).css('top'))
    }
};

Mautic.campaignBuilderUpdateEventList = function (groups, disabled) {
    mQuery.each(['Source', 'Action', 'Decision', 'Condition'], function(key, theGroup) {
        if (mQuery.inArray(theGroup, groups) !== -1) {
            mQuery('#'+theGroup +'Group').prop('disabled', disabled);
        } else {
            mQuery('#'+theGroup+'Group').prop('disabled', !disabled);
        }
    });

    mQuery('#CampaignEventSelector').trigger('chosen:updated');
};

/**
 *
 * @param endpoint
 * @param nameOnly
 * @returns {{endpointName: *, elementId: *}}
 */
Mautic.campaignBuilderGetEndpointDetails = function(endpoint) {
    var parts = endpoint.anchor.cssClass.split(' ');

    return {
        'anchorName': parts[0],
        'eventId': parts[1].replace('CampaignEvent_', ''),
        'elementId' : parts[1],
        'eventType': mQuery('#'+parts[1]).data('type')
    };
};

Mautic.campaignBuilderPrepareNewSource = function () {
    var newSourcePos = {
        left: mQuery(window).width()/2 - 100,
        top: 50
    };

    mQuery('#CampaignEvent_newsource').css(newSourcePos);

    // Activate chosen for sources
    mQuery('#CampaignEventPanel').css({
        left: newSourcePos.left - 50,
        top: newSourcePos.top + 35
    });

    Mautic.campaignBuilderUpdateEventList(['Action', 'Decision', 'Condition'], true);

    mQuery('#CampaignEventPanel').removeClass('hide');
    setTimeout(function () {
        mQuery('#CampaignEventSelector').trigger('chosen:open');
    }, 100);
};