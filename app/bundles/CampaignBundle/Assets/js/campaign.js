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
        // setup button clicks
        mQuery('#CampaignEventPanelGroups button').on('click', function() {
            var eventType = mQuery(this).data('type');
            Mautic.campaignBuilderUpdateEventList([eventType], false, 'lists', true);
        });

        mQuery('#CampaignEventPanelLists button').on('click', function() {
            Mautic.campaignBuilderUpdateEventList(Mautic.campaignBuilderAnchorClickedAllowedEvents, true, 'groups', true);
        });

        // set hover and double click functions for the event buttons
        mQuery('#CampaignCanvas .list-campaign-event, #CampaignCanvas .list-campaign-source').off('.eventbuttons')
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

        // setup chosen
        mQuery('.campaign-event-selector').on('chosen:showing_dropdown', function (event) {
            // Disable canvas scrolling
            mQuery('.builder-content').css('overflow', 'hidden');

            var thisSelect = mQuery(event.target).attr('id');
            Mautic.campaignBuilderUpdateEventListTooltips(thisSelect, false);

            mQuery('#'+thisSelect+'_chosen .chosen-search input').on('keydown.toolip', function () {
                // Destroy tooltips that are filtered out
                Mautic.campaignBuilderUpdateEventListTooltips(thisSelect, true);
            }).on('keyup.tooltip', function() {
                // Recreate tooltips for those left
                Mautic.campaignBuilderUpdateEventListTooltips(thisSelect, false);
            });
        });

        mQuery('.campaign-event-selector').on('chosen:hiding_dropdown', function (event) {
            // Re-enable canvas scrolling
            mQuery('.builder-content').css('overflow', 'auto');

            var thisSelect = mQuery(event.target).attr('id');
            Mautic.campaignBuilderUpdateEventListTooltips(thisSelect, true);

            mQuery('#'+thisSelect+'_chosen .chosen-search input').off('keyup.toolip')
                .off('keydown.tooltip');
        });

        mQuery('.campaign-event-selector').on('change', function() {
            // Hide events list

            if (!mQuery('#CampaignEvent_newsource').length) {
                // Hide the CampaignEventPanel if visible
                mQuery('#CampaignEventPanel').addClass('hide');
            }

            // Get the option clicked
            var thisId = mQuery(this).attr('id');
            var option  = mQuery('#'+thisId+' option[value="' + mQuery(this).val() + '"]');

            if (option.attr('data-href') && Mautic.campaignBuilderAnchorNameClicked) {
                var updatedUrl = option.attr('data-href').replace(/anchor=(.*?)$/, "anchor=" + Mautic.campaignBuilderAnchorNameClicked + "&anchorEventType=" + Mautic.campaignBuilderAnchorEventTypeClicked);
                // Replace the anchor in the URL with that clicked
                option.attr('data-href', updatedUrl);
            }

            // Deactivate chosen to kill tooltips
            mQuery('#'+thisId).trigger('chosen:close');

            // Display the modal with form
            Mautic.ajaxifyModal(option);

            // Reset the dropdown
            mQuery(this).val('');
            mQuery(this).trigger('chosen:updated');
        });

        mQuery('#CampaignCanvas').on('click', function(event) {
            if (!mQuery(event.target).parents('#CampaignCanvas').length && !mQuery('#CampaignEvent_newsource').length) {
                // Hide the CampaignEventPanel if visible
                mQuery('#CampaignEventPanel').addClass('hide');
            }
        });
    }
};

/**
 * Update chosen tooltips
 *
 * @param theSelect
 * @param destroy
 */
Mautic.campaignBuilderUpdateEventListTooltips = function(theSelect, destroy)
{
    mQuery('#'+theSelect+' option').each(function () {
        if (mQuery(this).attr('id')) {
            // Initiate a tooltip on each option since chosen doesn't copy over the data attributes
            var chosenOption = '#' + theSelect + '_chosen .option_' + mQuery(this).attr('id');

            if (destroy) {
                mQuery(chosenOption).tooltip('destroy');
            } else {
                mQuery(chosenOption).tooltip({html: true, container: 'body', placement: 'left'});
            }
        }
    });
}

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

    if (!response.success && Mautic.campaignBuilderConnectionRequiresUpdate) {
        // Modal exited - check to see if a connection needs to be removed
        Mautic.campaignBuilderInstance.detach(Mautic.campaignBuilderLastConnection);
    }
    Mautic.campaignBuilderConnectionRequiresUpdate = false;

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
        mQuery('#SourceList').trigger('chosen:updated');

        // Check to see if all sources have been deleted
        if (!mQuery('.list-campaign-source:not(#CampaignEvent_newsource_hide)').length) {
            mQuery('#CampaignEvent_newsource_hide').attr('id', 'CampaignEvent_newsource');
            Mautic.campaignBuilderPrepareNewSource();
        }

    } else if (response.updateHtml) {

        mQuery(eventId + " .campaign-event-content").html(response.updateHtml);
    } else if (response.sourceHtml) {
        mQuery('#campaignLeadSource_' + response.sourceType).prop('disabled', true);
        mQuery('#SourceList').trigger('chosen:updated');

        var newHtml = response.sourceHtml;

        if (mQuery('#CampaignEvent_newsource').length) {
            var x = mQuery('#CampaignEvent_newsource').position().left;
            var y = mQuery('#CampaignEvent_newsource').position().top;

            mQuery('#CampaignEvent_newsource').attr('id', 'CampaignEvent_newsource_hide');
            mQuery('#CampaignEventPanel').addClass('hide');
            var autoConnect = false;
        } else {
            //append content
            var x = parseInt(mQuery('#droppedX').val());
            var y = parseInt(mQuery('#droppedY').val());
            var autoConnect = true;
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
        if (autoConnect) {
            // Connect into last anchor clicked
            if (Mautic.campaignBuilderAnchorClicked.search('left') !== -1) {
                var source = domEventId + '_leadsourceright';
                var target = Mautic.campaignBuilderAnchorClicked;
            } else {
                var source = Mautic.campaignBuilderAnchorClicked;
                var target = domEventId + '_leadsourceleft';
            }

            Mautic.campaignBuilderInstance.connect({
                uuids: [
                    source,
                    target
                ]
            });
        }

        // If there are no other events, auto click add action
        if (!mQuery('.list-campaign-event').length) {
            mQuery('._jsPlumb_endpoint_anchor_leadsource.'+domEventId).trigger('click');
        }
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
            // Mark the connection so it can be removed if the form is cancelled
            Mautic.campaignBuilderConnectionRequiresUpdate = false;
            Mautic.campaignBuilderLastConnection           = info.connection;

            // If there is a switch between active/inactive anchors, reload the form
            var epDetails          = Mautic.campaignBuilderGetEndpointDetails(info.sourceEndpoint);
            var targetElementId    = info.targetEndpoint.elementId;
            var previousConnection = mQuery('#'+targetElementId).attr('data-connected')
            var editButton         = mQuery('#'+targetElementId).find('a.btn-edit');
            var editUrl            = editButton.attr('href');

            var anchorQueryParams  = '&anchor=' + epDetails.anchorName + "&anchorEventType=" + epDetails.eventType;
            if (editUrl.search('anchor=') !== -1) {
                editUrl.replace(/anchor=(.*?)$/, anchorQueryParams);
            } else {
                editUrl = editUrl + anchorQueryParams;
            }
            editButton.attr('data-href', editUrl);

            if (previousConnection && previousConnection != epDetails.anchorName && (previousConnection == 'no' || epDetails.anchorName == 'no')) {
                editButton.attr('data-prevent-dismiss', true);
                Mautic.campaignBuilderConnectionRequiresUpdate = true;

                editButton.trigger('click');
            }

            mQuery('#'+targetElementId).attr('data-connected', epDetails.anchorName);

            Mautic.campaignBuilderUpdateLabel(info.connection.targetId);
            info.targetEndpoint.setPaintStyle(
                {
                    fillStyle: info.connection.getPaintStyle().strokeStyle
                }
            );
            info.sourceEndpoint.setPaintStyle(
                {
                    fillStyle: info.connection.getPaintStyle().strokeStyle
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

            var currentConnections = info.sourceEndpoint.connections.length;
            // JavaScript counts index which still accounts for old connection
            currentConnections -= 1;
            if (!currentConnections) {
                info.sourceEndpoint.setPaintStyle(
                    {
                        fillStyle: "#d5d4d4"
                    }
                );
            }
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
        Mautic.campaignBuilderAnchorDefaultColor = '#d5d4d4';
        Mautic.campaignBuilderRegisterEndpoint('top', Mautic.campaignBuilderAnchorDefaultColor, true);
        Mautic.campaignBuilderRegisterEndpoint('bottom', Mautic.campaignBuilderAnchorDefaultColor);
        Mautic.campaignBuilderRegisterEndpoint('yes', Mautic.campaignBuilderAnchorDefaultColor, false, '#00b49c');
        Mautic.campaignBuilderRegisterEndpoint('no', Mautic.campaignBuilderAnchorDefaultColor, false, '#f86b4f');
        Mautic.campaignBuilderRegisterEndpoint('leadSource', Mautic.campaignBuilderAnchorDefaultColor);
        Mautic.campaignBuilderRegisterEndpoint('leadSourceLeft', Mautic.campaignBuilderAnchorDefaultColor, true, '#fdb933');
        Mautic.campaignBuilderRegisterEndpoint('leadSourceRight', Mautic.campaignBuilderAnchorDefaultColor, false, '#fdb933');

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
            'wiggleWidth': 30,
            'wiggleHeight': 50
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

/**
 * Validate a connection before it can be made
 *
 * @param params
 * @returns {boolean}
 */
Mautic.campaignBeforeDropCallback = function(params) {
    var sourceEndpoint = Mautic.campaignBuilderGetEndpointDetails(params.connection.endpoints[0]);
    var targetEndpoint = Mautic.campaignBuilderGetEndpointDetails(params.dropEndpoint);

    if (!Mautic.campaignBuilderValidateConnection(sourceEndpoint, targetEndpoint.event)){

        return false;
    }

    if (mQuery.inArray(targetEndpoint.anchorName, ['top', 'leadsourceleft', 'leadsourceright'])) {
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
    }

    //ensure that the connections are not looping back into the same event
    if (params.sourceId == params.targetId) {

        return false;
    }

    // ensure the map allows this connection
    var allowedConnections = Mautic.campaignBuilderConnectionsMap[sourceEndpoint.eventType][sourceEndpoint.anchorName][targetEndpoint.eventType];

    var allowed = mQuery.inArray(targetEndpoint.anchorName, allowedConnections) !== -1;

    if (allowed) {
        //ensure that a top only has one connection at a time unless connected from a source
        if (params.dropEndpoint.connections.length > 0) {

            // Replace the connection
            mQuery.each(params.dropEndpoint.connections, function(key, conn) {
                Mautic.campaignBuilderInstance.detach(conn);
            });
        }
    }

    return allowed;

};

/**
 * Enable/Disable timeframe settings if the toggle for immediate trigger is changed
 */
Mautic.campaignToggleTimeframes = function() {
    if (mQuery('#campaignevent_triggerMode_2').length) {
        var immediateChecked = mQuery('#campaignevent_triggerMode_0').prop('checked');
        var intervalChecked = mQuery('#campaignevent_triggerMode_1').prop('checked');
        var dateChecked = mQuery('#campaignevent_triggerMode_2').prop('checked');
    } else {
        var immediateChecked = false;
        var intervalChecked = mQuery('#campaignevent_triggerMode_0').prop('checked');
        var dateChecked = mQuery('#campaignevent_triggerMode_1').prop('checked');
    }

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

/**
 * Close campaign builder
 */
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

/**
 * Submit the campaign event form
 * @param e
 */
Mautic.submitCampaignEvent = function(e) {
    e.preventDefault();

    mQuery('#campaignevent_canvasSettings_droppedX').val(mQuery('#droppedX').val());
    mQuery('#campaignevent_canvasSettings_droppedY').val(mQuery('#droppedY').val());
    mQuery('#campaignevent_canvasSettings_decisionPath').val(mQuery('#decisionPath').val());

    mQuery('form[name="campaignevent"]').submit();
};

/**
 * Submit source form
 * @param e
 */
Mautic.submitCampaignSource = function(e) {
    e.preventDefault();

    mQuery('#campaign_leadsource_droppedX').val(mQuery('#droppedX').val());
    mQuery('#campaign_leadsource_droppedY').val(mQuery('#droppedY').val());

    mQuery('form[name="campaign_leadsource"]').submit();
};

/**
 * Register an endpoint with JsPlumb
 *
 * @param name
 * @param color
 * @param isTarget
 * @param connectorColor
 */
Mautic.campaignBuilderRegisterEndpoint = function (name, color, isTarget, connectorColor) {
    var isSource = true;
    if (isTarget === null) {
        // Both are allowed
        isTarget = true;
    } else {
        if (typeof isTarget == 'undefined') {
            isTarget = false;
        }
        if (isTarget) {
            isSource = false;
        }
    }

    if (!connectorColor) {
        connectorColor = color;
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
            strokeStyle: connectorColor,
            lineWidth: 1
        },
        beforeDrop: Mautic.campaignBeforeDropCallback
    }
};

/**
 * Register an anchor with JsPlumb
 *
 * @param names
 * @param el
 */
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

            // If this is a leadsourceleft or leadsourceright anchor - ensure there are source options available before allowing to add a new
            if (epDetails.anchorName == 'leadsourceleft' || epDetails.anchorName == 'leadsourceright') {
                if (mQuery('#SourceList option:enabled').length === 1) {
                    // Accounts for empty option

                    return;
                }
            }

            // Set color style
            endpoint.setPaintStyle(
                {
                    fillStyle: endpoint.connectorStyle.strokeStyle
                }
            );

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

            if (!endpoint.connections.length) {
                // Set color style
                endpoint.setPaintStyle(
                    {
                        fillStyle: Mautic.campaignBuilderAnchorDefaultColor
                    }
                );

            }
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

            // If this is a leadsourceleft or leadsourceright anchor - ensure there are source options available before allowing to add a new
            if (epDetails.anchorName == 'leadsourceleft' || epDetails.anchorName == 'leadsourceright') {
                if (mQuery('#SourceList option:enabled').length === 1) {
                    // Accounts for empty option

                    return;
                }
            }

            // Note the anchor so it can be auto attached after the event is created
            var epDetails = Mautic.campaignBuilderGetEndpointDetails(endpoint);
            var clickedAnchorName = epDetails.anchorName;
            Mautic.campaignBuilderAnchorClicked = endpoint.elementId+'_'+clickedAnchorName;
            Mautic.campaignBuilderAnchorNameClicked = clickedAnchorName;
            Mautic.campaignBuilderAnchorEventTypeClicked = epDetails.eventType;

            // Get the position of the event
            var elPos = Mautic.campaignBuilderGetEventPosition(endpoint.element)
            var spotFound = false,
                putLeft = elPos.left,
                putTop = elPos.top,
                direction = '', // xl, xr, yu, yd
                fullWidth = Mautic.campaignBuilderEventDimensions.width + Mautic.campaignBuilderEventDimensions.anchor,
                wiggleWidth = fullWidth + Mautic.campaignBuilderEventDimensions.wiggleWidth,
                fullHeight = Mautic.campaignBuilderEventDimensions.height + Mautic.campaignBuilderEventDimensions.anchor,
                wiggleHeight = fullHeight + Mautic.campaignBuilderEventDimensions.wiggleHeight
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
                                putLeft -= (w + Mautic.campaignBuilderEventDimensions.wiggleWidth);
                                if (putLeft <= 0) {
                                    putLeft = 0;
                                    // Ran out of room so go down
                                    direction = 'yd';
                                    putTop += fullHeight + Mautic.campaignBuilderEventDimensions.wiggleHeight;
                                }
                                break;
                            case 'xr':
                                if (putLeft + w + Mautic.campaignBuilderEventDimensions.wiggleWidth > windowWidth) {
                                    // Hit right canvas so start going down by default
                                    direction = 'yd';
                                    putLeft -= Mautic.campaignBuilderEventDimensions.wiggleWidth;
                                    putTop += fullHeight + Mautic.campaignBuilderEventDimensions.wiggleHeight;
                                } else {
                                    putLeft += (w + Mautic.campaignBuilderEventDimensions.wiggleWidth);
                                }
                                break;
                            case 'yu':
                                putTop -= (h - Mautic.campaignBuilderEventDimensions.wiggleHeight);
                                if (putTop <= 0) {
                                    putTop = 0;
                                    // Ran out of room going up so try the right
                                    direction = 'xr';
                                }
                                break;
                            case 'yd':
                                putTop += (h + Mautic.campaignBuilderEventDimensions.wiggleHeight);
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
            Mautic.campaignBuilderAnchorClickedAllowedEvents = allowedEvents;

            var el = (mQuery(event.target).hasClass('_jsPlumb_endpoint')) ? event.target : mQuery(event.target).parents('._jsPlumb_endpoint')[0];
            Mautic.campaignBuilderAnchorClickedPosition = Mautic.campaignBuilderGetEventPosition(el);
            Mautic.campaignBuilderUpdateEventList(allowedEvents, false, 'groups');

            // Disable the list items not allowed
            mQuery('.campaign-event-selector:not(#SourceList) option').prop('disabled', false);
            if ('source' == epDetails.eventType) {
                var checkSelects = ['#ActionList', '#DecisionList'];
            } else {
                var checkSelects = [(epDetails.eventType == 'decision') ? '#ActionList': '#DecisionList'];
            }

            mQuery.each(checkSelects, function(key, selectId) {
                mQuery(selectId + ' option').each(function () {
                    var optionVal = mQuery(this).val();
                    if (!Mautic.campaignBuilderValidateConnection(epDetails, optionVal)) {
                        mQuery(this).prop('disabled', true);
                    }
                });
                mQuery(selectId).trigger('chosen:updated');
            });
        });
    });
};

/**
 * Extract information about an event/endpoint from element
 *
 * @param el
 * @returns {{left: Number, top: Number}}
 */
Mautic.campaignBuilderGetEventPosition = function(el) {
    return {
        'left': parseInt(mQuery(el).css('left')),
        'top': parseInt(mQuery(el).css('top'))
    }
};

/**
 * Update the event select list
 *
 * @param groups
 * @param hidden
 * @param view
 * @param active
 * @param forcePosition
 */
Mautic.campaignBuilderUpdateEventList = function (groups, hidden, view, active, forcePosition) {
    var groupsEnabled = 0;
    var inGroupsView = ('groups' == view);

    if (groups.length === 1 && mQuery.inArray('Source', groups) !== -1 && !hidden) {
        // Force groups mode
        inGroupsView = false;
    }
    mQuery.each(['Source', 'Action', 'Decision', 'Condition'], function (key, theGroup) {
        if (mQuery.inArray(theGroup, groups) !== -1) {
            if (inGroupsView) {
                mQuery('#' + theGroup + 'GroupSelector').removeClass('hide');

                if ('source' != theGroup) {
                    groupsEnabled++;
                }
            } else {
                mQuery('#' + theGroup + 'GroupList').removeClass('hide');
            }
        } else {
            if (inGroupsView) {
                mQuery('#' + theGroup + 'GroupSelector').addClass('hide');
            } else {
                mQuery('#' + theGroup + 'GroupList').addClass('hide');
            }
        }
    });

    if (inGroupsView) {
        mQuery.each(groups, function (key, theGroup) {
            mQuery('#'+theGroup+'GroupSelector').removeClass(
                function (index, css) {
                    return (css.match(/col-(\S+)/g) || []).join(' ');
                }
            ).addClass('col-md-' + (12 / groupsEnabled));
        });

        var newWidth = (500 / 3) * groupsEnabled;
        if (newWidth >= mQuery(window).width()) {
            newWidth = mQuery(window).width() - 10;
        }

        var leftPos = (forcePosition) ? forcePosition.left : Mautic.campaignBuilderAnchorClickedPosition.left - (newWidth / 2 - 10);
        var topPos  = (forcePosition) ? forcePosition.top : Mautic.campaignBuilderAnchorClickedPosition.top + 25;
        mQuery('#CampaignEventPanel').css({
                left: (leftPos >=0 ) ? leftPos : 10,
                top: topPos,
                width: newWidth,
                height: 280
            });

        mQuery('#CampaignEventPanel').removeClass('hide');
        mQuery('#CampaignEventPanelGroups').removeClass('hide');
        mQuery('#CampaignEventPanelLists').addClass('hide');
    } else {
        var leftPos = (forcePosition) ? forcePosition.left : Mautic.campaignBuilderAnchorClickedPosition.left - 125;
        var topPos  = (forcePosition) ? forcePosition.top : Mautic.campaignBuilderAnchorClickedPosition.top + 25;
        mQuery('#CampaignEventPanel').css({
            left: (leftPos >= 0) ? leftPos : 10,
            top: topPos,
            width: 300,
            height: 80,
        });

        mQuery('#CampaignEventPanelGroups').addClass('hide');
        mQuery('#CampaignEventPanelLists').removeClass('hide');
        mQuery('#CampaignEventPanel').removeClass('hide');

        if (groups.length === 1) {
            setTimeout(function () {
                // Activate chosen
                mQuery('#CampaignEventPanelLists #' + groups[0] + 'List').trigger('chosen:open');
            }, 10);
        }
    }
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
        'eventType': mQuery('#'+parts[1]).data('type'),
        'event': mQuery('#'+parts[1]).data('event')
    };
};

/**
 * Display new source when required
 */
Mautic.campaignBuilderPrepareNewSource = function () {
    var newSourcePos = {
        left: mQuery(window).width()/2 - 100,
        top: 50
    };

    mQuery('#CampaignEvent_newsource').css(newSourcePos);

    Mautic.campaignBuilderUpdateEventList(['Source'], false, 'list', false, {
        left: newSourcePos.left - 50,
        top: newSourcePos.top + 35
    });
    mQuery('#SourceList').trigger('chosen:open');
};

/**
 *
 * @param epDetails
 * @param optionVal
 * @returns {boolean}
 */
Mautic.campaignBuilderValidateConnection = function (epDetails, checkEvent) {
    var valid = true;

    if ('source' == epDetails.eventType) {
        // If this is a source, do not show action/decisions that have type restrictions
        if (typeof Mautic.campaignBuilderConnectionRestrictions['action'] !== 'undefined' && typeof Mautic.campaignBuilderConnectionRestrictions['action'][checkEvent] !== 'undefined') {

            return false;
        }

        if (typeof Mautic.campaignBuilderConnectionRestrictions['decision'] !== 'undefined' && typeof Mautic.campaignBuilderConnectionRestrictions['decision'][checkEvent] !== 'undefined') {

            return false;
        }
    }

    if (typeof Mautic.campaignBuilderConnectionRestrictions[epDetails.eventType] !== 'undefined') {
        if (typeof Mautic.campaignBuilderConnectionRestrictions[epDetails.eventType][checkEvent] !== 'undefined') {
            if (mQuery.inArray(epDetails.event, Mautic.campaignBuilderConnectionRestrictions[epDetails.eventType][checkEvent]) === -1) {
                // Disable this one
                valid = false;
            }
        }
    }

    if (typeof Mautic.campaignBuilderConnectionRestrictions['anchors'][epDetails.eventType] !== 'undefined') {
        if (typeof Mautic.campaignBuilderConnectionRestrictions['anchors'][epDetails.eventType][checkEvent]) {
            mQuery(Mautic.campaignBuilderConnectionRestrictions['anchors'][epDetails.eventType][checkEvent]).each(function(key, anchor) {
                switch (anchor) {
                    case 'inaction':
                        anchor = 'no';
                        break;
                    case 'action':
                        anchor = 'yes';
                        break;
                }

                if (anchor == epDetails.anchorName) {
                    // Disable this one
                    valid = false;

                    return false;
                }
            });
        }
    }

    return valid;
}